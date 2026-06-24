<?php

namespace App\Service\DungeonRoute;

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
use App\Models\Season;
use App\Models\User;
use App\Repositories\Interfaces\MapIconRepositoryInterface;
use App\Service\DungeonRoute\Logging\DungeonRouteSaveServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Random\RandomException;

readonly class DungeonRouteSaveService implements DungeonRouteSaveServiceInterface
{
    public function __construct(
        private SeasonServiceInterface                  $seasonService,
        private ThumbnailServiceInterface               $thumbnailService,
        private DungeonRouteSaveServiceLoggingInterface $log,
        private MapIconRepositoryInterface              $mapIconRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     *
     * @throws Exception
     */
    public function save(DungeonRoute $dungeonRoute, array $validated): bool
    {
        $result = false;
        $new    = !$dungeonRoute->exists;

        $dungeonId = (int)($validated['dungeon_id'] ?? $dungeonRoute->dungeon_id);
        $this->log->saveStart($dungeonRoute->id ?? null, $new, $dungeonId);

        try {
            $result = (bool)DB::transaction(fn(): bool => $this->persist($dungeonRoute, $validated, $dungeonId, $new));
        } finally {
            $this->log->saveEnd($dungeonRoute->id ?? null, $result);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $validated
     *
     * @throws Exception
     */
    public function saveTemporary(DungeonRoute $dungeonRoute, array $validated): bool
    {
        $dungeonId = (int)($validated['dungeon_id'] ?? $dungeonRoute->dungeon_id);

        $this->log->saveTemporaryStart($dungeonId);

        $saveResult = false;

        try {
            $saveResult = (bool)DB::transaction(fn(): bool => $this->persistTemporary($dungeonRoute, $validated, $dungeonId));
        } finally {
            $this->log->saveTemporaryEnd($dungeonRoute->public_key ?? '', $saveResult);
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
                // Clone keeps the source's mapping version, so the start map icon id stays valid
                'dungeon_start_map_icon_id' => $source->dungeon_start_map_icon_id,

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
     * Hydrates and persists a (new or existing) route from a validated request, including all
     * of its child relations, affix groups and new-route side effects.
     *
     * @param  array<string, mixed> $validated
     * @throws RandomException
     */
    private function persist(DungeonRoute $dungeonRoute, array $validated, int $dungeonId, bool $new): bool
    {
        /** @var User|null $user */
        $user    = Auth::user();
        $dungeon = Dungeon::findOrFail($dungeonId);

        $userGameVersion = GameVersion::getUserOrDefaultGameVersion();
        $activeSeason    = $userGameVersion->has_seasons ? $this->resolveSeasonForEdit($dungeon) : null;

        $teamId    = (int)($validated['team_id'] ?? $dungeonRoute->team_id);
        $factionId = (int)($validated['faction_id'] ?? $dungeonRoute->faction_id);

        // Fetch the title if the user set anything
        $title = $validated['dungeon_route_title'] ?? $dungeonRoute->title;
        // Title slug CAN resolve to empty if they're just using special characters only
        if (empty($title) || empty(Str::slug((string)$title))) {
            $title = __($dungeon->name);
        }

        // New routes get the dungeon's current mapping version; existing routes keep their own
        $mappingVersionId = $new ? $dungeon->getCurrentMappingVersion()->id : $dungeonRoute->mapping_version_id;

        $attributes = [
            'dungeon_id' => $dungeonId,
            'team_id'    => $teamId > 0 ? $teamId : null,
            // If it was empty just set Unspecified instead
            'faction_id'                 => $factionId ?: 1,
            'seasonal_index'             => (int)($validated['seasonal_index'] ?? [$dungeonRoute->seasonal_index])[0],
            'teeming'                    => false,
            'pull_gradient'              => $validated['pull_gradient'] ?? '',
            'pull_gradient_apply_always' => (bool)($validated['pull_gradient_apply_always'] ?? false),
            'title'                      => $title,
            'description'                => $validated['dungeon_route_description'] ?? ($dungeonRoute->description ?? ''),
            'dungeon_difficulty'         => $this->resolveDungeonDifficulty($dungeon, isset($validated['dungeon_difficulty']) ? (int)$validated['dungeon_difficulty'] : null),
            'dungeon_start_map_icon_id'  => $this->resolveDungeonStartMapIconId($mappingVersionId, $validated['dungeon_start_map_icon_id'] ?? null),
        ] + $this->levelRangeAttributes($validated['dungeon_route_level'] ?? null, $activeSeason?->key_level_max);

        if ($new) {
            $attributes['author_id']          = $user?->id ?? -1; // @phpstan-ignore nullsafe.neverNull
            $attributes['public_key']         = DungeonRoute::generateRandomPublicKey();
            $attributes['mapping_version_id'] = $dungeon->getCurrentMappingVersion()->id;
        }

        if ($userGameVersion->has_seasons) {
            // Can still be null if there are no seasons for this dungeon, like in Classic
            $attributes['season_id'] = $activeSeason?->id;
        }

        if ($user?->hasRole(Role::ROLE_ADMIN)) {
            $attributes['demo'] = intval($validated['demo'] ?? 0) > 0;
        }

        // Remove all loaded relations - we have changed some IDs so the values should be re-fetched
        $dungeonRoute->unsetRelations();

        // Update or insert it
        if (!$dungeonRoute->forceFill($attributes)->save()) {
            $this->log->saveFailed($dungeonRoute->id ?? null);

            return false;
        }

        $this->syncRequestRelations($dungeonRoute, $validated);
        $this->applySelectedAffixGroups($dungeonRoute, $validated['route_select_affixes'] ?? [], $activeSeason, $new);

        // Instantly generate a placeholder thumbnail for new routes.
        if ($new) {
            $this->thumbnailService->queueThumbnailRefresh($dungeonRoute);
            $this->applyTemplateClone($dungeonRoute, $validated);
        }

        // Refresh the cards for this route
        DungeonRoute::dropCaches($dungeonRoute->id);

        return true;
    }

    /**
     * Hydrates and persists a temporary (sandbox) route, which uses hardcoded defaults and expires.
     *
     * @param  array<string, mixed> $validated
     * @throws Exception
     */
    private function persistTemporary(DungeonRoute $dungeonRoute, array $validated, int $dungeonId): bool
    {
        $dungeon = Dungeon::findOrFail($dungeonId);

        $userGameVersion = GameVersion::getUserOrDefaultGameVersion();
        // Can still be null if there are no seasons for this dungeon, like in Classic
        $activeSeason = $userGameVersion->has_seasons ? $this->resolveSeasonForTemporary($userGameVersion, $dungeon) : null;

        $mappingVersionId = $dungeon->getCurrentMappingVersion()->id;

        $attributes = [
            'author_id'                  => Auth::id() ?? -1,
            'public_key'                 => DungeonRoute::generateRandomPublicKey(),
            'dungeon_id'                 => $dungeonId,
            'mapping_version_id'         => $mappingVersionId,
            'season_id'                  => $activeSeason?->id,
            'faction_id'                 => 1,
            'seasonal_index'             => 0,
            'teeming'                    => false,
            'pull_gradient'              => '',
            'pull_gradient_apply_always' => false,
            'dungeon_difficulty'         => $this->resolveDungeonDifficulty($dungeon, isset($validated['dungeon_difficulty']) ? (int)$validated['dungeon_difficulty'] : null),
            'dungeon_start_map_icon_id'  => $this->resolveDungeonStartMapIconId($mappingVersionId, $validated['dungeon_start_map_icon_id'] ?? null),
            'title'                      => __('models.dungeonroute.title_temporary_route', ['dungeonName' => __($dungeon->name)]),
            'expires_at'                 => Carbon::now()->addHours(config('keystoneguru.sandbox_dungeon_route_expires_hours')),
        ] + $this->levelRangeAttributes($validated['dungeon_route_level'] ?? null, $activeSeason?->key_level_max);

        if (!$dungeonRoute->forceFill($attributes)->save()) {
            $this->log->saveTemporarySaveFailed($dungeonId);

            return false;
        }

        if ($activeSeason !== null) {
            $dungeonRoute->ensureAffixGroup($activeSeason);
        }

        return true;
    }

    /**
     * Syncs the route's user-selected child collections (attributes, classes, specs, races) from the request.
     *
     * @param array<string, mixed> $validated
     */
    private function syncRequestRelations(DungeonRoute $dungeonRoute, array $validated): void
    {
        $newAttributes = $validated['attributes'] ?? [];
        if (!empty($newAttributes)) {
            $this->syncChildModels(
                $dungeonRoute,
                DungeonRouteAttribute::class,
                'route_attribute_id',
                RouteAttribute::whereIn('id', $newAttributes)->pluck('id'),
            );
        }

        $newClasses = $validated['class'] ?? [];
        if (!empty($newClasses)) {
            $this->syncChildModels(
                $dungeonRoute,
                DungeonRoutePlayerClass::class,
                'character_class_id',
                CharacterClass::whereIn('id', $newClasses)->pluck('id'),
            );
        }

        $newSpecs = $validated['specialization'] ?? [];
        if (!empty($newSpecs)) {
            $this->syncChildModels(
                $dungeonRoute,
                DungeonRoutePlayerSpecialization::class,
                'character_class_specialization_id',
                CharacterClassSpecialization::whereIn('id', $newSpecs)->pluck('id'),
            );
        }

        // We don't _really_ care if this doesn't get saved properly, they can just set it again when editing.
        $newRaces = $validated['race'] ?? [];
        if (!empty($newRaces)) {
            $this->syncChildModels(
                $dungeonRoute,
                DungeonRoutePlayerRace::class,
                'character_race_id',
                $newRaces,
            );
        }
    }

    /**
     * Replaces all of a route's rows in a child pivot table with the given foreign-key IDs.
     *
     * @param class-string<Model>  $childModel
     * @param iterable<int|string> $ids
     */
    private function syncChildModels(DungeonRoute $dungeonRoute, string $childModel, string $foreignKey, iterable $ids): void
    {
        $childModel::where('dungeon_route_id', $dungeonRoute->id)->delete();

        $rows = [];
        foreach ($ids as $id) {
            $rows[] = [
                'dungeon_route_id' => $dungeonRoute->id,
                $foreignKey        => (int)$id,
            ];
        }

        if ($rows !== []) {
            $childModel::insert($rows);
        }
    }

    /**
     * Applies the user-selected affix groups, or ensures a default one for new routes without a selection.
     *
     * @param array<int, mixed> $newAffixes
     */
    private function applySelectedAffixGroups(DungeonRoute $dungeonRoute, array $newAffixes, ?Season $activeSeason, bool $new): void
    {
        if (empty($newAffixes)) {
            if ($new && $activeSeason !== null) {
                $dungeonRoute->ensureAffixGroup($activeSeason);
            }

            return;
        }

        $dungeonRoute->affixgroups()->delete();

        if ($activeSeason === null) {
            return;
        }

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

    /**
     * Clones a demo route's drawn relations into the route when the request asks for a template.
     *
     * @param array<string, mixed> $validated
     */
    private function applyTemplateClone(DungeonRoute $dungeonRoute, array $validated): void
    {
        // If the user requested a template route..
        if (!($validated['template'] ?? false)) {
            return;
        }

        // Check if there's a route that we can use as a template..
        $templateRoute = DungeonRoute::where('demo', true)
            ->where('dungeon_id', $dungeonRoute->dungeon_id)
            ->where('teeming', $dungeonRoute->teeming)
            ->first();

        // Only if the route was found!
        if ($templateRoute === null) {
            return;
        }

        $this->log->saveTemplateCloneStart($dungeonRoute->id, $templateRoute->id);
        $templateRoute->cloneRelationsInto($dungeonRoute, [
            $templateRoute->paths,
            $templateRoute->brushlines,
            $templateRoute->arrows,
            $templateRoute->killZones,
            $templateRoute->enemyRaidMarkers,
            $templateRoute->mapicons,
        ]);
        $this->log->saveTemplateCloneEnd($dungeonRoute->id);
    }

    /**
     * Resolves the dungeon difficulty for speedrun-enabled dungeons: keeps the chosen difficulty when it is one of the
     * dungeon's enabled speedrun difficulties, otherwise falls back to the first enabled difficulty.
     */
    private function resolveDungeonDifficulty(Dungeon $dungeon, ?int $difficulty): ?int
    {
        if ($difficulty !== null && $dungeon->speedrun_enabled) {
            $enabledDifficulties = $dungeon->getEnabledSpeedrunDifficulties();

            if (in_array($difficulty, $enabledDifficulties, true)) {
                return $difficulty;
            }

            return $enabledDifficulties[0] ?? $difficulty;
        }

        return $difficulty;
    }

    /**
     * Resolves the chosen dungeon start map icon, returning the id only when it is a dungeon start
     * icon that belongs to the given mapping version. Anything else (a different type, another
     * dungeon's icon, a stale mapping version) resolves to null, which later falls back to the first start.
     */
    private function resolveDungeonStartMapIconId(int $mappingVersionId, mixed $id): ?int
    {
        if ($id === null) {
            return null;
        }

        return $this->mapIconRepository->isDungeonStart((int)$id, $mappingVersionId) ? (int)$id : null;
    }

    /**
     * Builds the level_min/level_max attributes from a "min;max" range, or none when nothing was provided.
     *
     * @return array{level_min?: int, level_max?: int|null}
     */
    private function levelRangeAttributes(mixed $rawLevel, ?int $seasonKeyLevelMax): array
    {
        [$levelMin, $levelMax] = $this->parseLevelRange($rawLevel, $seasonKeyLevelMax);
        if ($levelMin === null) {
            return [];
        }

        return ['level_min' => $levelMin, 'level_max' => $levelMax];
    }

    private function resolveSeasonForEdit(Dungeon $dungeon): ?Season
    {
        return $this->seasonService->getUpcomingSeasonForDungeon($dungeon)
            ?? $this->seasonService->getMostRecentSeasonForDungeon($dungeon);
    }

    private function resolveSeasonForTemporary(GameVersion $gameVersion, Dungeon $dungeon): ?Season
    {
        return $this->seasonService->getCurrentSeason($gameVersion->expansion)
            ?? $this->seasonService->getMostRecentSeasonForDungeon($dungeon);
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
