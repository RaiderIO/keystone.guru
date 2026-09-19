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
     * slots included; a free-form collection one group per dungeon in order of first appearance. Routes that do not
     * match the collection follow in a trailing group. Expects the season's dungeons and every route's mapping
     * version to be loaded.
     *
     * @param  Collection<int, DungeonRoute>                $dungeonRoutes In collection order.
     * @return Collection<int, DungeonRouteCollectionGroup>
     */
    public function getDungeonRouteGroups(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): Collection;

    /**
     * The sections of the route picker for a collection of the passed game version and season: one per pool dungeon
     * for a season set, a single flat list otherwise, each offering the own routes that may join. Member routes that
     * do not match follow in a trailing section, from which they can only be removed.
     *
     * @param  Collection<int, DungeonRoute>                $ownDungeonRoutes    Every route the owner may collect.
     * @param  Collection<int, DungeonRoute>                $memberDungeonRoutes The routes currently in the collection.
     * @return Collection<int, DungeonRouteCollectionGroup>
     */
    public function getEditSections(
        ?GameVersion $gameVersion,
        ?Season      $season,
        Collection   $ownDungeonRoutes,
        Collection   $memberDungeonRoutes,
    ): Collection;

    /**
     * The enemy forces of each route against its mapping version's requirement ("500 / 498"), flagged as a warning
     * when the route falls short. Routes without a mapping version, or whose mapping version requires no enemy forces
     * (dungeons without enemy forces, such as most classic ones), get no entry. Expects the mapping versions loaded.
     *
     * @param  Collection<int, DungeonRoute>                    $dungeonRoutes
     * @return array<int, array{text: string, isWarning: bool}> Keyed by route id.
     */
    public function getEnemyForcesDetails(Collection $dungeonRoutes): array;

    /**
     * "Season 2 set · 5/8 dungeons" for a season set, "Cataclysm · 4 dungeons" for a free-form collection.
     *
     * @param Collection<int, DungeonRoute> $dungeonRoutes The routes to count, with their mapping versions loaded.
     */
    public function getKindLabel(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): string;

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
