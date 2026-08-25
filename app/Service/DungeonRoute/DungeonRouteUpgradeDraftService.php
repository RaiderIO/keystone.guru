<?php

namespace App\Service\DungeonRoute;

use App\Events\LiveSession\RouteReplacedEvent;
use App\Jobs\RefreshEnemyForces;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftGoneException;
use App\Service\DungeonRoute\Logging\DungeonRouteUpgradeDraftServiceLoggingInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Override;
use Throwable;

readonly class DungeonRouteUpgradeDraftService implements DungeonRouteUpgradeDraftServiceInterface
{
    public function __construct(
        private DungeonRouteServiceInterface                    $dungeonRouteService,
        private ThumbnailServiceInterface                       $thumbnailService,
        private DungeonRouteUpgradeDraftServiceLoggingInterface $log,
    ) {
    }

    #[Override]
    public function findOrCreateDraft(DungeonRoute $original): DungeonRoute
    {
        $this->log->findOrCreateDraftStart($original->id);

        $draft = null;

        try {
            if ($original->is_upgrade_draft) {
                throw new UpgradeDraftException('An upgrade draft cannot have an upgrade draft of its own.');
            }

            // A sandbox route has no audience to protect - it should be upgraded in place
            if ($original->isSandbox()) {
                throw new UpgradeDraftException('A sandbox route cannot have an upgrade draft.');
            }

            $draft = $original->upgradeDraft;
            if ($draft !== null) {
                $this->log->findOrCreateDraftExistingDraftFound($original->id, $draft->id);

                return $draft;
            }

            $draft = DB::transaction(function () use ($original): DungeonRoute {
                $draft = DungeonRoute::create([
                    'public_key'                  => DungeonRoute::generateRandomPublicKey(),
                    'upgrade_of_dungeon_route_id' => $original->id,
                    // A draft is not a clone - it is going to become the original again
                    'clone_of'           => null,
                    'author_id'          => $original->author_id,
                    'dungeon_id'         => $original->dungeon_id,
                    'mapping_version_id' => $original->mapping_version_id,
                    'season_id'          => $original->season_id,
                    'faction_id'         => $original->faction_id,
                    // The team must be able to work on the draft as well
                    'team_id' => $original->team_id,
                    // Belt and braces - the saving hook forces this too
                    'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
                    // Still valid here; upgradeMappingVersion() remaps it onto the new mapping version
                    'dungeon_start_map_icon_id' => $original->dungeon_start_map_icon_id,
                    // Deliberately unchanged - no clone prefix, this route replaces the original
                    'title'                      => $original->title,
                    'description'                => $original->description,
                    'level_min'                  => $original->level_min,
                    'level_max'                  => $original->level_max,
                    'difficulty'                 => $original->difficulty,
                    'dungeon_difficulty'         => $original->dungeon_difficulty,
                    'seasonal_index'             => $original->seasonal_index,
                    'teeming'                    => $original->teeming,
                    'enemy_forces'               => $original->enemy_forces,
                    'pull_gradient'              => $original->pull_gradient,
                    'pull_gradient_apply_always' => $original->pull_gradient_apply_always,
                ]);

                // demo is deliberately not fillable, so it is set through the query builder instead
                DungeonRoute::query()->whereKey($draft->id)->update([
                    'demo' => $original->demo,
                ]);

                $original->cloneRelationsInto($draft, $this->contentRelationsOf($original));

                return $draft->refresh();
            });

            // Outside the transaction: upgradeMappingVersion() opens its own and drops its own caches
            try {
                $this->dungeonRouteService->upgradeMappingVersion($draft);
            } catch (Throwable $throwable) {
                // The clone has already committed, so a throw here would otherwise leave behind a draft
                // that is a byte identical copy of the original, still on the old mapping version - and
                // the existing-draft branch above would keep returning it forever, leaving the author
                // with no way to retry.
                $this->log->findOrCreateDraftUpgradeFailed($original->id, $draft->id);
                $draft->delete();

                throw $throwable;
            }

            $draft->refresh();

            return $draft;
        } finally {
            $this->log->findOrCreateDraftEnd($draft instanceof DungeonRoute ? $draft->id : 0);
        }
    }

    #[Override]
    public function apply(DungeonRoute $draft, bool $enforcePublishInvariant = true): DungeonRoute
    {
        if (!$draft->is_upgrade_draft) {
            throw new UpgradeDraftException('This route is not an upgrade draft, so there is nothing to apply.');
        }

        $this->log->applyStart($draft->id, $draft->upgrade_of_dungeon_route_id);

        try {
            $original = DB::transaction(function () use ($draft, $enforcePublishInvariant): DungeonRoute {
                // Pessimistic lock on the original - this is the two-people-hit-Apply guard
                $original = DungeonRoute::query()
                    ->whereKey($draft->upgrade_of_dungeon_route_id)
                    ->lockForUpdate()
                    ->first();

                if ($original === null) {
                    throw new UpgradeDraftException('The route this draft upgrades no longer exists.');
                }

                // Re-fetch the draft under the lock; if it is gone, a concurrent Apply, discard or take-over
                // won the race. This is also the arbiter between two Auto Route Creator regenerations of the
                // same route, which is why it throws its own exception type - the loser has to tell this
                // apart from the refusals it cannot retry (#4297).
                $lockedDraft = DungeonRoute::query()->whereKey($draft->id)->first();
                if ($lockedDraft === null || $lockedDraft->upgrade_of_dungeon_route_id !== $original->id) {
                    throw new UpgradeDraftGoneException('This upgrade draft has already been applied or discarded.');
                }

                // The original's publish invariant (DungeonRoutePolicy::publish()) must still hold after
                // Apply replaces its content - a published route cannot go live missing required enemies
                // the new mapping version added, even though nothing here directly asked to publish it
                if (
                    $original->published_state_id !== PublishedState::ALL[PublishedState::UNPUBLISHED]
                    && !$lockedDraft->hasKilledAllRequiredEnemies()
                ) {
                    if ($enforcePublishInvariant) {
                        throw new UpgradeDraftException(__('policy.apply_upgrade_draft_not_all_required_enemies_killed'));
                    }

                    $this->log->applyPublishInvariantBypassed($lockedDraft->id, $original->id);
                }

                $original->deleteContentRelations();

                // The query builder rather than $original->update(): Eloquent's dirty tracking survives a
                // rollback, so on a retried transaction the save would silently become a no-op (#4250).
                // The transaction below is deliberately not retried for the same family of reasons, but
                // this keeps the write correct regardless of who calls it.
                DungeonRoute::query()->whereKey($original->id)->update($this->applyAttributes($lockedDraft));

                $original->refresh();

                $lockedDraft->cloneRelationsInto($original, $this->contentRelationsOf($lockedDraft));

                // Guarantees enemy_forces against the freshly copied kill zones. Safe inside the
                // transaction: handle() does its own find(), so it cannot see a stale relation.
                new RefreshEnemyForces($original->id)->handle();

                // cloneRelationsInto() copies rather than moves, so the draft still owns its own rows and
                // its deleting hook cleans exactly those up
                $lockedDraft->delete();

                return $original;
            });
            // Deliberately no retry count: a retry would re-run deleteContentRelations() after the copy
            // was rolled back, leaving the original with deleted content and no replacement (#4250).

            DungeonRoute::dropCaches($original->id);
            $this->thumbnailService->queueThumbnailRefresh($original);

            // Best effort - the live session is accepted as busted, connected clients are told to refresh.
            // ContextEvent needs an acting user; without one (queue, console) there is nobody to attribute
            // the refresh to and the notice is simply skipped.
            /** @var User|null $user */
            $user = Auth::user();
            if ($user !== null) {
                foreach ($original->livesessions as $liveSession) {
                    broadcast(new RouteReplacedEvent($liveSession, $user));
                }
            }

            $this->log->applyEnd($original->id);

            return $original->refresh();
        } catch (Throwable $throwable) {
            $this->log->applyEnd($draft->upgrade_of_dungeon_route_id ?? 0);

            throw $throwable;
        }
    }

    #[Override]
    public function discard(DungeonRoute $draft): void
    {
        if (!$draft->is_upgrade_draft) {
            throw new UpgradeDraftException('This route is not an upgrade draft, so there is nothing to discard.');
        }

        $this->log->discardStart($draft->id);

        $draftId = $draft->id;

        // The deleting hook does everything else
        DB::transaction(static function () use ($draft): void {
            $draft->delete();
        });

        DungeonRoute::dropCaches($draftId);

        $this->log->discardEnd($draftId);
    }

    /**
     * The single list of content relations that make up a route, so that draft creation and apply
     * cannot drift apart.
     *
     * Deliberately NOT included: tags, ratings, favorites, pinnedByUsers, livesessions, mdtImport,
     * metrics, pageviews, thumbnails and their jobs, scheduledPublish, challengeModeRun. The last
     * four of those need nothing on apply anyway - they key off dungeon_route_id, and the original's
     * id is preserved.
     *
     * @return array<int, mixed>
     */
    private function contentRelationsOf(DungeonRoute $dungeonRoute): array
    {
        return [
            $dungeonRoute->playerraces,
            $dungeonRoute->playerclasses,
            $dungeonRoute->playerspecializations,
            $dungeonRoute->affixGroups,
            $dungeonRoute->paths,
            $dungeonRoute->brushlines,
            $dungeonRoute->arrows,
            $dungeonRoute->killZones,
            $dungeonRoute->pridefulEnemies,
            $dungeonRoute->enemyRaidMarkers,
            // routeMapIcons, never mapicons - the latter widens itself to team wide icons that do not
            // belong to this route
            $dungeonRoute->routeMapIcons,
            $dungeonRoute->routeattributesraw,
        ];
    }

    /**
     * Every column that Apply copies from the draft onto the original. Everything not listed here is
     * preserved on the original - its identity (id, public_key, author, clone_of), its published state,
     * its audience (views, popularity, rating), its thumbnails and its sandbox expiry.
     *
     * @return array<string, mixed>
     */
    private function applyAttributes(DungeonRoute $draft): array
    {
        return [
            'mapping_version_id'        => $draft->mapping_version_id,
            'dungeon_start_map_icon_id' => $draft->dungeon_start_map_icon_id,
            'dungeon_id'                => $draft->dungeon_id,
            // season_id, dungeon_difficulty and demo are all written by DungeonRouteSaveService::persist(),
            // so a draft can genuinely diverge on them - assign, never assume identical
            'season_id'                  => $draft->season_id,
            'faction_id'                 => $draft->faction_id,
            'title'                      => $draft->title,
            'description'                => $draft->description,
            'level_min'                  => $draft->level_min,
            'level_max'                  => $draft->level_max,
            'difficulty'                 => $draft->difficulty,
            'dungeon_difficulty'         => $draft->dungeon_difficulty,
            'seasonal_index'             => $draft->seasonal_index,
            'teeming'                    => $draft->teeming,
            'demo'                       => $draft->demo,
            'pull_gradient'              => $draft->pull_gradient,
            'pull_gradient_apply_always' => $draft->pull_gradient_apply_always,
            // Copied as well as recomputed by RefreshEnemyForces, so the value stays right even if the
            // refresh is ever a no-op
            'enemy_forces' => $draft->enemy_forces,
        ];
    }
}
