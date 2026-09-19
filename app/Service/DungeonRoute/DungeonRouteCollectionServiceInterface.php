<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

interface DungeonRouteCollectionServiceInterface
{
    /**
     * The routes of a collection as it is shown: a season set has one slot per pool dungeon in pool order, empty
     * slots included; a free-form collection one group per dungeon in order of first appearance. Expects the
     * season's dungeons and every route's dungeon to be loaded.
     *
     * @param  Collection<int, DungeonRoute>                $dungeonRoutes In collection order.
     * @return Collection<int, DungeonRouteCollectionGroup>
     */
    public function getDungeonRouteGroups(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): Collection;

    /**
     * The sections of the route picker for a collection of the passed game version and season: one per pool dungeon
     * for a season set, a single flat list otherwise, each offering the own routes that may join.
     *
     * @param  Collection<int, DungeonRoute>                $ownDungeonRoutes Every route the owner may collect.
     * @return Collection<int, DungeonRouteCollectionGroup>
     */
    public function getEditSections(GameVersion $gameVersion, ?Season $season, Collection $ownDungeonRoutes): Collection;

    /**
     * How many distinct dungeons the passed routes cover - of the season's pool for a season set.
     *
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    public function getCoveredDungeonCount(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): int;

    /**
     * Sorts collections for the overview: sets of the current season first, then free-form collections, then sets
     * of any other season, each most recently updated first.
     *
     * @param  Collection<int, DungeonRouteCollection> $dungeonRouteCollections
     * @return Collection<int, DungeonRouteCollection>
     */
    public function sortForOverview(Collection $dungeonRouteCollections): Collection;

    /**
     * The seasons a new season set of the game version may be bound to, newest first. Empty for a game version
     * without seasons.
     *
     * @return Collection<int, Season>
     */
    public function getSelectableSeasons(GameVersion $gameVersion): Collection;

    /**
     * The current season of the game version's expansion, or null for a game version without seasons.
     */
    public function getCurrentSeason(GameVersion $gameVersion): ?Season;
}
