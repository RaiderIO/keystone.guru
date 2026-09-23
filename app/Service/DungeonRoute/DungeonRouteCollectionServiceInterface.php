<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

interface DungeonRouteCollectionServiceInterface
{
    public const string ADD_BLOCKED_GAME_VERSION = 'game_version';
    public const string ADD_BLOCKED_SEASON       = 'season';
    public const string ADD_BLOCKED_FULL         = 'full';

    /**
     * Why a route that is not in the collection cannot be added to it: one of the ADD_BLOCKED_* constants, or null
     * when it can. A route of another game version (or without a mapping version) is reported before one of another
     * season, and either before a full collection. Expects the route's mapping version to be loaded.
     */
    public function getAddBlockedReason(DungeonRouteCollection $dungeonRouteCollection, DungeonRoute $dungeonRoute, int $routeCount): ?string;

    /**
     * The passed routes that may be in a collection of the passed game version and season, in passed order. Expects
     * every route's mapping version to be loaded.
     *
     * @param  Collection<int, DungeonRoute> $dungeonRoutes
     * @return Collection<int, DungeonRoute>
     */
    public function filterMatchingDungeonRoutes(GameVersion $gameVersion, ?Season $season, Collection $dungeonRoutes): Collection;

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
     * for a season set, a single flat list otherwise, each offering the own routes that may join. The routes already
     * in the collection are always offered, whether they may still join or not - the picker posts what it offers, so
     * leaving one out would drop it from the collection on the next save.
     *
     * @param  Collection<int, DungeonRoute>                $ownDungeonRoutes    Every route the owner may collect.
     * @param  Collection<int, DungeonRoute>                $memberDungeonRoutes The routes currently in the collection.
     * @return Collection<int, DungeonRouteCollectionGroup>
     */
    public function getEditSections(
        GameVersion $gameVersion,
        ?Season     $season,
        Collection  $ownDungeonRoutes,
        Collection  $memberDungeonRoutes,
    ): Collection;

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

    /**
     * The collection's own routes whose published state is less visible than the collection's own - the candidates
     * for the "make them visible too" confirmation offered after raising a collection's published state.
     * Authorization is not applied here: the caller still has to filter with `Gate::allows('publish', ...)` per
     * route, since only some of them may belong to the acting user.
     *
     * @return Collection<int, DungeonRoute>
     */
    public function getRoutesLessVisibleThanCollection(DungeonRouteCollection $dungeonRouteCollection): Collection;

    /**
     * The subset of $dungeonRoutes that $user may raise to the collection's published state: routes they own (any
     * route for an admin), in the collection's team when that state is Team, whose dungeon and the user's benefits
     * allow that state, and that pass the publish policy.
     *
     * @param  Collection<int, DungeonRoute> $dungeonRoutes
     * @return Collection<int, DungeonRoute>
     */
    public function filterRoutesRaisableToCollection(
        DungeonRouteCollection $dungeonRouteCollection,
        Collection             $dungeonRoutes,
        User                   $user,
    ): Collection;

    /**
     * Sets every passed route to the collection's published state and logs the change on each route's team. Does
     * not authorize: pass only routes returned by filterRoutesRaisableToCollection().
     *
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    public function raiseRoutesToCollection(DungeonRouteCollection $dungeonRouteCollection, Collection $dungeonRoutes): void;
}
