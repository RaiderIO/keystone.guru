<?php

namespace App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

class DungeonRouteCollectionService implements DungeonRouteCollectionServiceInterface
{
    private const int OVERVIEW_RANK_CURRENT_SEASON = 0;
    private const int OVERVIEW_RANK_FREE_FORM      = 1;
    private const int OVERVIEW_RANK_OTHER_SEASON   = 2;

    public function __construct(
        private readonly SeasonServiceInterface $seasonService,
    ) {
    }

    public function getAddBlockedReason(DungeonRouteCollection $dungeonRouteCollection, DungeonRoute $dungeonRoute, int $routeCount): ?string
    {
        $mappingVersion = $dungeonRoute->mappingVersion;

        if ($mappingVersion === null ||
            ($dungeonRouteCollection->game_version_id !== null && $mappingVersion->game_version_id !== $dungeonRouteCollection->game_version_id)) {
            return self::ADD_BLOCKED_GAME_VERSION;
        }

        if (!$dungeonRouteCollection->mayContainDungeonRoute($dungeonRoute)) {
            return self::ADD_BLOCKED_SEASON;
        }

        if ($routeCount >= DungeonRouteCollection::MAX_ROUTES) {
            return self::ADD_BLOCKED_FULL;
        }

        return null;
    }

    public function filterMatchingDungeonRoutes(?GameVersion $gameVersion, ?Season $season, Collection $dungeonRoutes): Collection
    {
        $kind = new DungeonRouteCollection([
            'game_version_id' => $gameVersion?->id,
            'season_id'       => $season?->id,
        ]);

        return $dungeonRoutes
            ->filter(static fn(DungeonRoute $dungeonRoute): bool => $kind->mayContainDungeonRoute($dungeonRoute))
            ->values();
    }

    public function getDungeonRouteGroups(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): Collection
    {
        [$matchingDungeonRoutes, $foreignDungeonRoutes] = $dungeonRoutes->partition(
            static fn(DungeonRoute $dungeonRoute): bool => $dungeonRouteCollection->mayContainDungeonRoute($dungeonRoute),
        );

        $season       = $dungeonRouteCollection->season;
        $poolDungeons = $dungeonRouteCollection->isSeasonSet() && $season !== null ? $season->dungeons : collect();

        $groups = $this->groupPerDungeon($poolDungeons, $matchingDungeonRoutes->values());

        return $this->appendForeignGroup($groups, $foreignDungeonRoutes->values());
    }

    public function getEditSections(
        ?GameVersion $gameVersion,
        ?Season      $season,
        Collection   $ownDungeonRoutes,
        Collection   $memberDungeonRoutes,
    ): Collection {
        $kind = new DungeonRouteCollection([
            'game_version_id' => $gameVersion?->id,
            'season_id'       => $season?->id,
        ]);

        $selectableDungeonRoutes = $ownDungeonRoutes
            ->filter(static fn(DungeonRoute $dungeonRoute): bool => $kind->mayContainDungeonRoute($dungeonRoute))
            ->values();

        $sections = $season === null
            ? collect([new DungeonRouteCollectionGroup(null, $selectableDungeonRoutes)])
            : $this->groupPerDungeon($season->dungeons, $selectableDungeonRoutes);

        return $this->appendForeignGroup(
            $sections,
            $memberDungeonRoutes
                ->reject(static fn(DungeonRoute $dungeonRoute): bool => $kind->mayContainDungeonRoute($dungeonRoute))
                ->values(),
        );
    }

    public function getKindLabel(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): string
    {
        $coveredDungeonIds = $dungeonRoutes
            ->filter(static fn(DungeonRoute $dungeonRoute): bool => $dungeonRouteCollection->mayContainDungeonRoute($dungeonRoute))
            ->pluck('dungeon_id')
            ->unique();

        $season = $dungeonRouteCollection->season;
        if ($dungeonRouteCollection->isSeasonSet() && $season !== null) {
            $poolDungeonIds = $season->dungeons->pluck('id');

            return __('view_collection.kind.season_set', [
                'season'  => $season->name,
                'covered' => $coveredDungeonIds->intersect($poolDungeonIds)->count(),
                'total'   => $poolDungeonIds->count(),
            ]);
        }

        $gameVersion = $dungeonRouteCollection->gameVersion ?? GameVersion::getDefaultGameVersion();

        return trans_choice('view_collection.kind.free_form', $coveredDungeonIds->count(), [
            'game_version' => __($gameVersion->name),
            'count'        => $coveredDungeonIds->count(),
        ]);
    }

    public function sortForOverview(Collection $dungeonRouteCollections): Collection
    {
        return $dungeonRouteCollections
            ->sortBy([
                fn(DungeonRouteCollection $a, DungeonRouteCollection $b): int => $this->getOverviewRank($a) <=> $this->getOverviewRank($b),
                static fn(DungeonRouteCollection $a, DungeonRouteCollection $b): int => $b->updated_at <=> $a->updated_at,
            ])
            ->values();
    }

    public function getSelectableSeasons(GameVersion $gameVersion): Collection
    {
        if (!$gameVersion->has_seasons) {
            return collect();
        }

        return Season::query()
            ->with(['expansion'])
            ->where('expansion_id', $gameVersion->expansion_id)
            ->where('active', true)
            ->orderByDesc('start')
            ->get();
    }

    public function getCurrentSeason(GameVersion $gameVersion): ?Season
    {
        if (!$gameVersion->has_seasons) {
            return null;
        }

        return $this->seasonService->getCurrentSeason($gameVersion->expansion);
    }

    private function getOverviewRank(DungeonRouteCollection $dungeonRouteCollection): int
    {
        if (!$dungeonRouteCollection->isSeasonSet()) {
            return self::OVERVIEW_RANK_FREE_FORM;
        }

        $gameVersion   = $dungeonRouteCollection->gameVersion;
        $currentSeason = $gameVersion !== null ? $this->getCurrentSeason($gameVersion) : null;

        return $currentSeason !== null && $currentSeason->id === $dungeonRouteCollection->season_id
            ? self::OVERVIEW_RANK_CURRENT_SEASON
            : self::OVERVIEW_RANK_OTHER_SEASON;
    }

    /**
     * One group per pool dungeon, in pool order and empty ones included, followed by one group per other dungeon the
     * routes are for, in order of first appearance.
     *
     * @param  Collection<int, Dungeon>                     $poolDungeons
     * @param  Collection<int, DungeonRoute>                $dungeonRoutes
     * @return Collection<int, DungeonRouteCollectionGroup>
     */
    private function groupPerDungeon(Collection $poolDungeons, Collection $dungeonRoutes): Collection
    {
        $dungeonRoutesPerDungeon = $dungeonRoutes->groupBy('dungeon_id');
        $poolDungeonIds          = $poolDungeons->pluck('id')->all();

        $poolGroups = $poolDungeons->map(static fn(Dungeon $dungeon): DungeonRouteCollectionGroup => new DungeonRouteCollectionGroup(
            $dungeon,
            $dungeonRoutesPerDungeon->get($dungeon->id, collect())->values(),
        ));

        $otherGroups = $dungeonRoutesPerDungeon
            ->reject(static fn(Collection $group, int $dungeonId): bool => in_array($dungeonId, $poolDungeonIds, true))
            ->map(static fn(Collection $group): DungeonRouteCollectionGroup => new DungeonRouteCollectionGroup(
                $group->first()->dungeon,
                $group->values(),
            ));

        return $poolGroups->concat($otherGroups->values())->values();
    }

    /**
     * @param  Collection<int, DungeonRouteCollectionGroup> $groups
     * @param  Collection<int, DungeonRoute>                $foreignDungeonRoutes
     * @return Collection<int, DungeonRouteCollectionGroup>
     */
    private function appendForeignGroup(Collection $groups, Collection $foreignDungeonRoutes): Collection
    {
        if ($foreignDungeonRoutes->isEmpty()) {
            return $groups;
        }

        return $groups->push(new DungeonRouteCollectionGroup(null, $foreignDungeonRoutes, false));
    }
}
