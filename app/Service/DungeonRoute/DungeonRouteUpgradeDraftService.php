<?php

namespace App\Service\DungeonRoute;

use App\Events\LiveSession\RouteReplacedEvent;
use App\Jobs\RefreshEnemyForces;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\DungeonRoute\DungeonRouteDraftSource;
use App\Models\MDTImport;
use App\Models\PublishedState;
use App\Models\User;
use App\Repositories\Interfaces\DungeonStartRepositoryInterface;
use App\Service\DungeonRoute\Exceptions\PendingUpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\StaleUpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftGoneException;
use App\Service\DungeonRoute\Logging\DungeonRouteUpgradeDraftServiceLoggingInterface;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\MDT\Models\ImportStringDetails;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Override;
use Throwable;

readonly class DungeonRouteUpgradeDraftService implements DungeonRouteUpgradeDraftServiceInterface
{
    public const string MDT_IMPORT_LOSS_PATHS                  = 'paths';
    public const string MDT_IMPORT_LOSS_BRUSHLINES             = 'brushlines';
    public const string MDT_IMPORT_LOSS_ARROWS                 = 'arrows';
    public const string MDT_IMPORT_LOSS_MAP_ICONS              = 'map_icons';
    public const string MDT_IMPORT_LOSS_PULL_COLORS            = 'pull_colors';
    public const string MDT_IMPORT_LOSS_PULL_DESCRIPTIONS      = 'pull_descriptions';
    public const string MDT_IMPORT_LOSS_RAID_MARKERS           = 'raid_markers';
    public const string MDT_IMPORT_LOSS_PLAYER_CLASSES         = 'player_classes';
    public const string MDT_IMPORT_LOSS_PLAYER_SPECIALIZATIONS = 'player_specializations';
    public const string MDT_IMPORT_LOSS_PLAYER_RACES           = 'player_races';
    public const string MDT_IMPORT_LOSS_ROUTE_ATTRIBUTES       = 'route_attributes';

    public function __construct(
        private DungeonRouteServiceInterface                    $dungeonRouteService,
        private ThumbnailServiceInterface                       $thumbnailService,
        private DungeonRouteUpgradeDraftServiceLoggingInterface $log,
        private MDTImportStringServiceInterface                 $mdtImportStringService,
        private MappingServiceInterface                         $mappingService,
        private DungeonStartRepositoryInterface                 $dungeonStartRepository,
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

            try {
                $draft = DB::transaction(function () use ($original): DungeonRoute {
                    $draft = DungeonRoute::create([
                        'public_key'                  => DungeonRoute::generateRandomPublicKey(),
                        'upgrade_of_dungeon_route_id' => $original->id,
                        'draft_source'                => DungeonRouteDraftSource::MappingUpgrade,
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
                        'dungeon_start_id' => $original->dungeon_start_id,
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
            } catch (UniqueConstraintViolationException) {
                // Lost the race to create the draft (dungeon_routes_upgrade_of_unique) - a concurrent
                // request for the same original won and already committed its draft, so return that one
                // instead of failing this request too
                $draft = DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $original->id)->firstOrFail();
                $this->log->findOrCreateDraftExistingDraftFound($original->id, $draft->id);

                return $draft;
            }

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
    public function createDraftFromMdtString(
        DungeonRoute $original,
        string       $mdtString,
        Collection   $warnings,
        ?int         $discardExistingDraftId = null,
    ): DungeonRoute {
        $this->log->createDraftFromMdtStringStart($original->id, $discardExistingDraftId);

        $draft = null;

        try {
            if ($original->is_upgrade_draft) {
                throw new UpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_route_is_draft'));
            }

            if ($original->isSandbox()) {
                throw new UpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_route_is_sandbox'));
            }

            $this->assertExistingDraftMayBeDiscarded(
                DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $original->id)->value('id'),
                $discardExistingDraftId,
            );

            // Resolved against the original's game version rather than the acting user's, since Apply moves the
            // original onto the draft's mapping version
            $gameVersion = $original->mappingVersion->gameVersion;

            // Only parses - nothing is persisted until the string is known to fit this route
            $details = $this->mdtImportStringService->setEncodedString($mdtString)->getDetails(collect(), collect(), $gameVersion);
            $this->assertMdtStringFitsRoute($original, $details);

            // The replacement is imported and upgraded as a standalone route first; the existing draft is only
            // discarded once it can be swapped for a replacement that is known to be complete. The import persists
            // its route, pulls and objects in separate writes; one transaction (deliberately not retried) keeps a
            // failure part-way from leaving a partial route behind that counts against the author's route limit.
            $draft = DB::transaction(fn(): DungeonRoute => $this->mdtImportStringService->setEncodedString($mdtString)->getDungeonRoute(
                $warnings,
                collect(),
                sandbox: false,
                save: true,
                gameVersion: $gameVersion,
            ));

            try {
                $this->copyOriginalMetadataInto($original, $draft);

                $draft->refresh();

                // The string is on the newest mapping version MDT ships, but keystone.guru is ahead of MDT
                $currentMappingVersion = $draft->dungeon->getCurrentMappingVersion($draft->mappingVersion->gameVersion);
                if ($currentMappingVersion !== null && $currentMappingVersion->id !== $draft->mapping_version_id) {
                    $this->log->createDraftFromMdtStringUpgradingMappingVersion($draft->id, $currentMappingVersion->id);
                    $this->dungeonRouteService->upgradeMappingVersion($draft);
                }

                $discardedDraftId = $this->swapInAsDraft($original, $draft, $discardExistingDraftId);

                $draft->refresh();
            } catch (UniqueConstraintViolationException $exception) {
                // A draft of $original was created concurrently (dungeon_routes_upgrade_of_unique)
                $this->log->createDraftFromMdtStringFailed($original->id, $draft->id, $exception->getMessage());
                $draft->delete();
                $draft = null;

                throw new PendingUpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_pending_draft'));
            } catch (Throwable $throwable) {
                $this->log->createDraftFromMdtStringFailed($original->id, $draft->id, $throwable->getMessage());
                $draft->delete();
                $draft = null;

                throw $throwable;
            }

            if ($discardedDraftId !== null) {
                DungeonRoute::dropCaches($discardedDraftId);
            }

            return $draft;
        } finally {
            $this->log->createDraftFromMdtStringEnd($draft instanceof DungeonRoute ? $draft->id : 0);
        }
    }

    #[Override]
    public function getMdtImportContentLoss(DungeonRoute $original): array
    {
        $counts = [
            self::MDT_IMPORT_LOSS_PATHS                  => $original->paths()->count(),
            self::MDT_IMPORT_LOSS_BRUSHLINES             => $original->brushlines()->count(),
            self::MDT_IMPORT_LOSS_ARROWS                 => $original->arrows()->count(),
            self::MDT_IMPORT_LOSS_MAP_ICONS              => $original->routeMapIcons()->count(),
            self::MDT_IMPORT_LOSS_PULL_COLORS            => $original->killZones()->whereNotNull('color')->where('color', '!=', '')->count(),
            self::MDT_IMPORT_LOSS_PULL_DESCRIPTIONS      => $original->killZones()->whereNotNull('description')->where('description', '!=', '')->count(),
            self::MDT_IMPORT_LOSS_RAID_MARKERS           => $original->enemyRaidMarkers()->count(),
            self::MDT_IMPORT_LOSS_PLAYER_CLASSES         => $original->playerclasses()->count(),
            self::MDT_IMPORT_LOSS_PLAYER_SPECIALIZATIONS => $original->playerspecializations()->count(),
            self::MDT_IMPORT_LOSS_PLAYER_RACES           => $original->playerraces()->count(),
            self::MDT_IMPORT_LOSS_ROUTE_ATTRIBUTES       => $original->routeattributesraw()->count(),
        ];

        return array_filter($counts, static fn(int $count): bool => $count > 0);
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
                        throw new UpgradeDraftException(
                            $lockedDraft->getEffectiveDraftSource() === DungeonRouteDraftSource::MdtImport
                                ? __('policy.apply_mdt_import_draft_not_all_required_enemies_killed')
                                : __('policy.apply_upgrade_draft_not_all_required_enemies_killed'),
                        );
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

                // The import that produced this content now describes the original, replacing its previous one
                if ($lockedDraft->getEffectiveDraftSource() === DungeonRouteDraftSource::MdtImport) {
                    MDTImport::query()->where('dungeon_route_id', $original->id)->delete();
                    MDTImport::query()->where('dungeon_route_id', $lockedDraft->id)->update(['dungeon_route_id' => $original->id]);
                }

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
     * @param int|null $existingDraftId        The draft $original has right now, if any.
     * @param int|null $discardExistingDraftId The draft the author confirmed discarding, if any.
     *
     * @throws PendingUpgradeDraftException When there is a draft and discarding it was not confirmed.
     * @throws StaleUpgradeDraftException   When there is a draft, but it is not the one the author confirmed discarding.
     */
    private function assertExistingDraftMayBeDiscarded(?int $existingDraftId, ?int $discardExistingDraftId): void
    {
        if ($existingDraftId === null) {
            return;
        }

        if ($discardExistingDraftId === null) {
            throw new PendingUpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_pending_draft'));
        }

        if ($existingDraftId !== $discardExistingDraftId) {
            throw new StaleUpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_draft_changed'));
        }
    }

    /**
     * Rejects, before anything is persisted, a string the preview found errors in, that is for another dungeon,
     * that resolved to another game version than the route's, or that was built against an MDT mapping
     * older than the newest one MDT ships for the dungeon.
     *
     * @throws UpgradeDraftException
     */
    private function assertMdtStringFitsRoute(DungeonRoute $original, ImportStringDetails $details): void
    {
        if ($details->getErrors()->isNotEmpty()) {
            throw new UpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_string_has_errors', [
                'errors' => $details->getErrors()
                    ->map(static fn(ImportWarning $error): string => $error->getMessage())
                    ->unique()
                    ->implode(' '),
            ]));
        }

        if ($details->getDungeon()->id !== $original->dungeon_id) {
            throw new UpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_other_dungeon', [
                'stringDungeon' => __($details->getDungeon()->name),
                'routeDungeon'  => __($original->dungeon->name),
            ]));
        }

        $stringMappingVersion = $details->getMappingVersion();
        if ($stringMappingVersion === null) {
            return;
        }

        if ($stringMappingVersion->game_version_id !== $original->mappingVersion->game_version_id) {
            throw new UpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_other_game_version'));
        }

        $newestMdtMappingVersion = $this->mappingService->getNewestMdtSyncedMappingVersion(
            $details->getDungeon(),
            $stringMappingVersion->game_version_id,
        );

        if ($newestMdtMappingVersion !== null && $stringMappingVersion->version < $newestMdtMappingVersion->version) {
            throw new UpgradeDraftException(__('services.dungeonroute.upgrade_draft.mdt_import_outdated_mapping'));
        }
    }

    /**
     * Gives the freshly imported route the original's metadata and affixes: Apply copies those from the draft,
     * and the original's must survive the import. The route does not become a draft until swapInAsDraft().
     *
     * @throws Throwable
     */
    private function copyOriginalMetadataInto(DungeonRoute $original, DungeonRoute $draft): void
    {
        $dungeonStartId = $this->findOriginalDungeonStartIdFor($original, $draft);

        DB::transaction(static function () use ($original, $draft, $dungeonStartId): void {
            // The query builder: demo is not fillable, and a retried Eloquent save would silently no-op
            DungeonRoute::query()->whereKey($draft->id)->update([
                'draft_source'       => DungeonRouteDraftSource::MdtImport->value,
                'author_id'          => $original->author_id,
                'team_id'            => $original->team_id,
                'season_id'          => $original->season_id,
                'faction_id'         => $original->faction_id,
                'title'              => $original->title,
                'description'        => $original->description,
                'level_min'          => $original->level_min,
                'level_max'          => $original->level_max,
                'difficulty'         => $original->difficulty,
                'dungeon_difficulty' => $original->dungeon_difficulty,
                // Follows the affix groups, which come from the original as well
                'seasonal_index'             => $original->seasonal_index,
                'pull_gradient'              => $original->pull_gradient,
                'pull_gradient_apply_always' => $original->pull_gradient_apply_always,
                'demo'                       => $original->demo,
                'published_state_id'         => PublishedState::ALL[PublishedState::UNPUBLISHED],
                'dungeon_start_id'           => $dungeonStartId,
            ]);

            DungeonRouteAffixGroup::query()->where('dungeon_route_id', $draft->id)->delete();
            DungeonRouteAffixGroup::query()->insert(
                $original->affixGroups()->pluck('affix_group_id')
                    ->map(static fn(int $affixGroupId): array => [
                        'dungeon_route_id' => $draft->id,
                        'affix_group_id'   => $affixGroupId,
                    ])
                    ->all(),
            );
        });
    }

    /**
     * The original's chosen dungeon start, as it exists in the mapping version the string was imported on.
     * Null when the original has no chosen start or the start has no match there, which falls back to the
     * mapping version's first start.
     */
    private function findOriginalDungeonStartIdFor(DungeonRoute $original, DungeonRoute $draft): ?int
    {
        if ($original->dungeon_start_id === null || $original->mapping_version_id === $draft->mapping_version_id) {
            return $original->dungeon_start_id;
        }

        return $this->dungeonStartRepository->findMatchingDungeonStartIdInMappingVersion(
            $original->dungeon_start_id,
            $draft->mapping_version_id,
        );
    }

    /**
     * Makes the fully imported and upgraded $draft the draft of $original, discarding the existing draft in the
     * same transaction, under a lock on the original so that concurrent imports and Apply serialise.
     *
     * @return int|null The id of the discarded draft, if there was one.
     *
     * @throws PendingUpgradeDraftException       When $original has a draft and discarding it was not confirmed.
     * @throws StaleUpgradeDraftException         When $original's draft is not the one discarding was confirmed for.
     * @throws UniqueConstraintViolationException When $original gained a draft in the meantime.
     * @throws Throwable
     */
    private function swapInAsDraft(DungeonRoute $original, DungeonRoute $draft, ?int $discardExistingDraftId): ?int
    {
        // Deliberately no retry count: a retry after the existing draft's delete was rolled back could only
        // repeat the same writes, and the caller removes the replacement on any failure
        return DB::transaction(function () use ($original, $draft, $discardExistingDraftId): ?int {
            $lockedOriginal = DungeonRoute::query()->whereKey($original->id)->lockForUpdate()->first();
            if ($lockedOriginal === null) {
                throw new UpgradeDraftException('The route this draft is for no longer exists.');
            }

            $existingDraft = DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $lockedOriginal->id)->first();
            $this->assertExistingDraftMayBeDiscarded($existingDraft?->id, $discardExistingDraftId);

            if ($existingDraft !== null) {
                $this->log->createDraftFromMdtStringDiscardingExistingDraft($lockedOriginal->id, $existingDraft->id);
                // The deleting hook does everything else
                $existingDraft->delete();
            }

            DungeonRoute::query()->whereKey($draft->id)->update([
                'upgrade_of_dungeon_route_id' => $lockedOriginal->id,
            ]);

            return $existingDraft?->id;
        });
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
            'mapping_version_id' => $draft->mapping_version_id,
            'dungeon_start_id'   => $draft->dungeon_start_id,
            'dungeon_id'         => $draft->dungeon_id,
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
