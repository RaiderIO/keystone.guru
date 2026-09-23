<?php

namespace App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

class DungeonRouteCollectionService implements DungeonRouteCollectionServiceInterface
{
    private const int OVERVIEW_RANK_CURRENT_SEASON = 0;
    private const int OVERVIEW_RANK_FREE_FORM      = 1;
    private const int OVERVIEW_RANK_OTHER_SEASON   = 2;

    public function __construct(
        private readonly SeasonServiceInterface    $seasonService,
        private readonly SeasonRepositoryInterface $seasonRepository,
    ) {
    }

    public function getAddBlockedReason(DungeonRouteCollection $dungeonRouteCollection, DungeonRoute $dungeonRoute, int $routeCount): ?string
    {
        $mappingVersion = $dungeonRoute->mappingVersion;

        if ($mappingVersion === null || $mappingVersion->game_version_id !== $dungeonRouteCollection->game_version_id) {
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

    public function filterMatchingDungeonRoutes(GameVersion $gameVersion, ?Season $season, Collection $dungeonRoutes): Collection
    {
        $kind = new DungeonRouteCollection([
            'game_version_id' => $gameVersion->id,
            'season_id'       => $season?->id,
        ]);

        return $dungeonRoutes
            ->filter(static fn(DungeonRoute $dungeonRoute): bool => $kind->mayContainDungeonRoute($dungeonRoute))
            ->values();
    }

    public function getDungeonRouteGroups(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): Collection
    {
        $season = $dungeonRouteCollection->season;

        return $this->groupPerDungeon(
            $dungeonRouteCollection->isSeasonSet() && $season !== null ? $season->dungeons : collect(),
            $dungeonRoutes->values(),
        );
    }

    public function getEditSections(
        GameVersion $gameVersion,
        ?Season     $season,
        Collection  $ownDungeonRoutes,
        Collection  $memberDungeonRoutes,
    ): Collection {
        $kind = new DungeonRouteCollection([
            'game_version_id' => $gameVersion->id,
            'season_id'       => $season?->id,
        ]);

        $selectableDungeonRoutes = $memberDungeonRoutes
            ->concat($ownDungeonRoutes->filter(static fn(DungeonRoute $dungeonRoute): bool => $kind->mayContainDungeonRoute($dungeonRoute)))
            ->unique('id')
            ->values();

        if ($season === null) {
            return collect([new DungeonRouteCollectionGroup(null, $selectableDungeonRoutes)]);
        }

        return $this->groupPerDungeon($season->dungeons, $selectableDungeonRoutes);
    }

    public function getCoveredDungeonCount(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): int
    {
        $coveredDungeonIds = $dungeonRoutes->pluck('dungeon_id')->unique();

        $season = $dungeonRouteCollection->season;
        if ($dungeonRouteCollection->isSeasonSet() && $season !== null) {
            $coveredDungeonIds = $coveredDungeonIds->intersect($season->dungeons->pluck('id'));
        }

        return $coveredDungeonIds->count();
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

        return $this->seasonRepository->getActiveSeasonsForExpansion($gameVersion->expansion);
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

        $currentSeason = $this->getCurrentSeason($dungeonRouteCollection->gameVersion);

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

    public function getRoutesLessVisibleThanCollection(DungeonRouteCollection $dungeonRouteCollection): Collection
    {
        return DungeonRoute::query()
            ->join('dungeon_route_collection_routes', 'dungeon_route_collection_routes.dungeon_route_id', '=', 'dungeon_routes.id')
            ->where('dungeon_route_collection_routes.dungeon_route_collection_id', $dungeonRouteCollection->id)
            ->where('dungeon_routes.published_state_id', '<', $dungeonRouteCollection->published_state_id)
            ->select('dungeon_routes.*')
            ->get();
    }
}
