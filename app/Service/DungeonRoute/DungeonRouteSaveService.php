<?php

namespace App\Service\DungeonRoute;

use App\Http\Requests\DungeonRoute\DungeonRouteSubmitTemporaryFormRequest;
use App\Models\Affix;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\RouteAttribute;
use App\Models\User;
use App\Service\DungeonRoute\Logging\DungeonRouteSaveServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

readonly class DungeonRouteSaveService implements DungeonRouteSaveServiceInterface
{
    public function __construct(
        private SeasonServiceInterface                  $seasonService,
        private ThumbnailServiceInterface               $thumbnailService,
        private DungeonRouteSaveServiceLoggingInterface $log,
    ) {
    }

    /**
     * @throws Exception
     */
    public function saveFromRequest(DungeonRoute $dungeonRoute, FormRequest $request): bool
    {
        $result    = false;
        $new       = !$dungeonRoute->exists;
        $validated = $request->validated();

        $dungeonId = (int)($validated['dungeon_id'] ?? $dungeonRoute->dungeon_id);
        $this->log->saveFromRequestStart($dungeonRoute->id ?? null, $new, $dungeonId);

        try {
            DB::transaction(function () use ($dungeonRoute, $new, $validated, $dungeonId, &$result): void {
                /** @var User|null $user */
                $user = Auth::user();

                $dungeonRoute->dungeon_id = $dungeonId;
                $dungeon                  = Dungeon::findOrFail($dungeonId);
                if ($new) {
                    $dungeonRoute->author_id          = $user?->id ?? -1; // @phpstan-ignore nullsafe.neverNull
                    $dungeonRoute->public_key         = DungeonRoute::generateRandomPublicKey();
                    $dungeonRoute->mapping_version_id = $dungeon->getCurrentMappingVersion()->id;
                }

                $teamIdFromRequest     = (int)($validated['team_id'] ?? $dungeonRoute->team_id);
                $dungeonRoute->team_id = $teamIdFromRequest > 0 ? $teamIdFromRequest : null;

                $dungeonRoute->faction_id = (int)($validated['faction_id'] ?? $dungeonRoute->faction_id);
                // If it was empty just set Unspecified instead
                $dungeonRoute->faction_id = empty($dungeonRoute->faction_id) ? 1 : $dungeonRoute->faction_id;

                $userGameVersion = GameVersion::getUserOrDefaultGameVersion();
                $activeSeason    = null;
                if ($userGameVersion->has_seasons) {
                    $activeSeason = $this->seasonService->getUpcomingSeasonForDungeon($dungeon) ??
                        $this->seasonService->getMostRecentSeasonForDungeon($dungeon);
                    // Can still be null if there are no seasons for this dungeon, like in Classic
                    $dungeonRoute->season_id = $activeSeason->id ?? null;
                    $dungeonRoute->setRelation('season', $activeSeason);
                }

                $dungeonRoute->seasonal_index = (int)($validated['seasonal_index'] ?? [$dungeonRoute->seasonal_index])[0];
                $dungeonRoute->teeming        = false; // (int)$request->get('teeming', $dungeonRoute->teeming) ?? 0;

                $dungeonRoute->pull_gradient              = $validated['pull_gradient'] ?? '';
                $dungeonRoute->pull_gradient_apply_always = (bool)($validated['pull_gradient_apply_always'] ?? false);

                // Fetch the title if the user set anything
                $dungeonRoute->title       = $validated['dungeon_route_title'] ?? $dungeonRoute->title;
                $dungeonRoute->description = $validated['dungeon_route_description'] ?? ($dungeonRoute->description ?? '');
                // Title slug CAN resolve to empty if they're just using special characters only
                if (empty($dungeonRoute->title) || empty($dungeonRoute->getTitleSlug())) {
                    $dungeonRoute->title = __($dungeon->name);
                }

                [$levelMin, $levelMax] = $this->parseLevelRange($validated['dungeon_route_level'] ?? null, $activeSeason?->key_level_max);
                if ($levelMin !== null) {
                    $dungeonRoute->level_min = $levelMin;
                    $dungeonRoute->level_max = $levelMax;
                }

                if ($user?->hasRole(Role::ROLE_ADMIN)) {
                    $dungeonRoute->demo = intval($validated['demo'] ?? 0) > 0;
                }

                $dungeonRoute->dungeon_difficulty = $validated['dungeon_difficulty'] ?? null;
                if ($dungeonRoute->dungeon_difficulty !== null && $dungeon->speedrun_enabled) {
                    $dungeonRoute->dungeon_difficulty = $dungeon->speedrun_difficulty_10_man_enabled ?
                        Dungeon::DIFFICULTY_10_MAN : Dungeon::DIFFICULTY_25_MAN;
                }

                // Remove all loaded relations - we have changed some IDs so the values should be re-fetched
                $dungeonRoute->unsetRelations();

                // Update or insert it
                if ($dungeonRoute->save()) {
                    $newAttributes = $validated['attributes'] ?? [];
                    if (!empty($newAttributes)) {
                        $dungeonRoute->routeattributesraw()->delete();
                        $validAttributeIds = RouteAttribute::whereIn('id', $newAttributes)->pluck('id');
                        foreach ($validAttributeIds as $id) {
                            DungeonRouteAttribute::create([
                                'dungeon_route_id'   => $dungeonRoute->id,
                                'route_attribute_id' => $id,
                            ]);
                        }
                    }

                    $newClasses = $validated['class'] ?? [];
                    if (!empty($newClasses)) {
                        $dungeonRoute->playerclasses()->delete();
                        $validClassIds = CharacterClass::whereIn('id', $newClasses)->pluck('id');
                        foreach ($validClassIds as $id) {
                            DungeonRoutePlayerClass::create([
                                'dungeon_route_id'   => $dungeonRoute->id,
                                'character_class_id' => $id,
                            ]);
                        }
                    }

                    $newSpecs = $validated['specialization'] ?? [];
                    if (!empty($newSpecs)) {
                        $dungeonRoute->playerspecializations()->delete();
                        $validSpecIds = CharacterClassSpecialization::whereIn('id', $newSpecs)->pluck('id');
                        foreach ($validSpecIds as $id) {
                            DungeonRoutePlayerSpecialization::create([
                                'dungeon_route_id'                  => $dungeonRoute->id,
                                'character_class_specialization_id' => $id,
                            ]);
                        }
                    }

                    $newRaces = $validated['race'] ?? [];
                    if (!empty($newRaces)) {
                        $dungeonRoute->playerraces()->delete();
                        // We don't _really_ care if this doesn't get saved properly, they can just set it again when editing.
                        foreach ($newRaces as $value) {
                            DungeonRoutePlayerRace::create([
                                'dungeon_route_id'  => $dungeonRoute->id,
                                'character_race_id' => (int)$value,
                            ]);
                        }
                    }

                    $newAffixes = $validated['route_select_affixes'] ?? [];
                    if (!empty($newAffixes)) {
                        $dungeonRoute->affixgroups()->delete();

                        if ($activeSeason !== null) {
                            foreach ($newAffixes as $value) {
                                $value = (int)$value;

                                // Use the already-loaded collection to avoid N+1
                                $affixGroup = $activeSeason->affixGroups->firstWhere('id', $value);

                                if ($affixGroup === null) {
                                    // Attempted to assign an affix that the dungeon cannot have - abort it
                                    continue;
                                }

                                // Check disabled to support dungeons not being tied to expansions but to seasons instead.
                                // Impact is that people could assign affixes to routes that don't make sense if they edit the request, meh w/e
                                // Skip any affixes that don't exist, and don't match our current expansion
                                // if (!AffixGroup::where('id', $value)->where('expansion_id', $dungeonRoute->dungeon->expansion_id)->exists()) {
                                //     continue;
                                // }

                                // Do not add affixes that do not belong to our Teeming selection
                                if ($affixGroup->id > 0 && $dungeonRoute->teeming != $affixGroup->hasAffix(Affix::AFFIX_TEEMING)) {
                                    continue;
                                }

                                DungeonRouteAffixGroup::create([
                                    'dungeon_route_id' => $dungeonRoute->id,
                                    'affix_group_id'   => $affixGroup->id,
                                ]);
                            }

                            // Reload the affixes relation
                            $dungeonRoute->load('affixes');
                        }
                    } elseif ($new && $activeSeason !== null) {
                        $dungeonRoute->ensureAffixGroup($activeSeason);
                    }

                    // Instantly generate a placeholder thumbnail for new routes.
                    if ($new) {
                        $this->thumbnailService->queueThumbnailRefresh($dungeonRoute);

                        // If the user requested a template route..
                        if ($validated['template'] ?? false) {
                            // Check if there's a route that we can use as a template..
                            $templateRoute = DungeonRoute::where('demo', true)
                                ->where('dungeon_id', $dungeonRoute->dungeon_id)
                                ->where('teeming', $dungeonRoute->teeming)
                                ->first();

                            // Only if the route was found!
                            if ($templateRoute !== null) {
                                $this->log->saveFromRequestTemplateCloneStart($dungeonRoute->id, $templateRoute->id);
                                $templateRoute->cloneRelationsInto($dungeonRoute, [
                                    $templateRoute->paths,
                                    $templateRoute->brushlines,
                                    $templateRoute->arrows,
                                    $templateRoute->killZones,
                                    $templateRoute->enemyRaidMarkers,
                                    $templateRoute->mapicons,
                                ]);
                                $this->log->saveFromRequestTemplateCloneEnd($dungeonRoute->id);
                            }
                        }
                    }

                    // Refresh the cards for this route
                    DungeonRoute::dropCaches($dungeonRoute->id);

                    $result = true;
                } else {
                    $this->log->saveFromRequestSaveFailed($dungeonRoute->id ?? null);
                }
            });
        } finally {
            $this->log->saveFromRequestEnd($dungeonRoute->id ?? null, $result);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function saveTemporaryFromRequest(
        DungeonRoute                           $dungeonRoute,
        DungeonRouteSubmitTemporaryFormRequest $request,
    ): bool {
        $validated = $request->validated();
        $dungeonId = (int)($validated['dungeon_id'] ?? $dungeonRoute->dungeon_id);

        $this->log->saveTemporaryFromRequestStart($dungeonId);

        $saveResult = false;

        try {
            DB::transaction(function () use ($dungeonRoute, $validated, $dungeonId, &$saveResult): void {
                $dungeonRoute->author_id  = Auth::id() ?? -1;
                $dungeonRoute->public_key = DungeonRoute::generateRandomPublicKey();

                $dungeonRoute->dungeon_id         = $dungeonId;
                $dungeon                          = Dungeon::findOrFail($dungeonId);
                $dungeonRoute->mapping_version_id = $dungeon->getCurrentMappingVersion()->id;

                $userGameVersion = GameVersion::getUserOrDefaultGameVersion();
                $activeSeason    = null;
                if ($userGameVersion->has_seasons) {
                    $activeSeason = $this->seasonService->getCurrentSeason(
                        $userGameVersion->expansion,
                    ) ?? $this->seasonService->getMostRecentSeasonForDungeon($dungeon);
                    // Can still be null if there are no seasons for this dungeon, like in Classic
                    $dungeonRoute->season_id = $activeSeason->id ?? null;
                }

                $dungeonRoute->faction_id                 = 1;
                $dungeonRoute->seasonal_index             = 0;
                $dungeonRoute->teeming                    = false;
                $dungeonRoute->pull_gradient              = '';
                $dungeonRoute->pull_gradient_apply_always = false;

                $dungeonRoute->dungeon_difficulty = $validated['dungeon_difficulty'] ?? null;
                if ($dungeonRoute->dungeon_difficulty !== null && $dungeon->speedrun_enabled) {
                    $dungeonRoute->dungeon_difficulty = $dungeon->speedrun_difficulty_10_man_enabled ?
                        Dungeon::DIFFICULTY_10_MAN : Dungeon::DIFFICULTY_25_MAN;
                }

                $dungeonRoute->title = __('models.dungeonroute.title_temporary_route', ['dungeonName' => __($dungeon->name)]);

                [$levelMin, $levelMax] = $this->parseLevelRange($validated['dungeon_route_level'] ?? null, $activeSeason?->key_level_max);
                if ($levelMin !== null) {
                    $dungeonRoute->level_min = $levelMin;
                    $dungeonRoute->level_max = $levelMax;
                }

                $dungeonRoute->expires_at = Carbon::now()->addHours(config('keystoneguru.sandbox_dungeon_route_expires_hours'));

                $saveResult = $dungeonRoute->save();
                if ($saveResult && $activeSeason !== null) {
                    $dungeonRoute->ensureAffixGroup($activeSeason);
                } elseif (!$saveResult) {
                    $this->log->saveTemporaryFromRequestSaveFailed($dungeonId);
                }
            });
        } finally {
            $this->log->saveTemporaryFromRequestEnd($dungeonRoute->public_key ?? '', $saveResult);
        }

        return $saveResult;
    }

    public function cloneRoute(DungeonRoute $source, bool $unpublished = true): DungeonRoute
    {
        $this->log->cloneRouteStart($source->id, $unpublished);

        $thumbnailsCopied = false;

        $clone = DB::transaction(function () use ($source, $unpublished, &$thumbnailsCopied): DungeonRoute {
            $clone = DungeonRoute::create([
                'public_key'         => DungeonRoute::generateRandomPublicKey(),
                'clone_of'           => $source->public_key,
                'author_id'          => Auth::id() ?? -1,
                'dungeon_id'         => $source->dungeon_id,
                'mapping_version_id' => $source->mapping_version_id,
                'season_id'          => $source->season_id,
                'faction_id'         => $source->faction_id,
                'published_state_id' => $unpublished ? PublishedState::ALL[PublishedState::UNPUBLISHED] : $source->published_state_id,

                // Do not clone team_id, user assigns the team himself
                'team_id'        => null,
                'title'          => __('models.dungeonroute.title_clone', ['routeTitle' => $source->title]),
                'description'    => $source->description,
                'seasonal_index' => $source->seasonal_index,
                'teeming'        => $source->teeming,
                'enemy_forces'   => $source->enemy_forces,
                'level_min'      => $source->level_min,
                'level_max'      => $source->level_max,
            ]);

            $source->cloneRelationsInto($clone, [
                $source->playerraces,
                $source->playerclasses,
                $source->affixGroups,
                $source->paths,
                $source->brushlines,
                $source->arrows,
                $source->killZones,
                $source->pridefulEnemies,
                $source->enemyRaidMarkers,
                $source->mapicons,
                $source->routeattributesraw,
            ]);

            $thumbnailsCopied = $this->thumbnailService->copyThumbnails($source, $clone)?->isNotEmpty() ?? false;
            if ($thumbnailsCopied) {
                $clone->update([
                    'thumbnail_refresh_queued_at' => $source->thumbnail_refresh_queued_at,
                    'thumbnail_updated_at'        => $source->thumbnail_updated_at,
                ]);
            }

            return $clone;
        });

        $this->log->cloneRouteEnd($clone->id, $thumbnailsCopied);

        return $clone;
    }

    /**
     * Parses a "min;max" level range string.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function parseLevelRange(mixed $rawLevel, int|null $seasonKeyLevelMax): array
    {
        if ($rawLevel === null) {
            return [null, null];
        }

        $parts  = explode(';', (string)$rawLevel);
        $min    = (int)$parts[0];
        $maxRaw = isset($parts[1]) ? (int)$parts[1] : $seasonKeyLevelMax;
        $max    = $maxRaw !== null ? (int)$maxRaw : null;

        return [$min, $max];
    }
}
