<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use Illuminate\Support\Collection;

interface DungeonRouteKillZoneServiceInterface
{
    /**
     * Gets the summed enemy forces for each kill zone (pull) in the given route, ordered by the kill
     * zone's index, along with whether that pull contains a boss. Used to render the "route
     * fingerprint" bar graph.
     *
     * @return Collection<int, KillZoneEnemyForces>
     */
    public function getEnemyForcesPerKillZone(DungeonRoute $dungeonRoute): Collection;

    /**
     * Batched variant of {@see self::getEnemyForcesPerKillZone()} - fetches the per-pull enemy forces
     * for an entire collection of routes (e.g. a leaderboard page) in O(1) queries instead of one
     * query per route, keyed by dungeon route id.
     *
     * @param  Collection<int, DungeonRoute>                         $dungeonRoutes
     * @return Collection<int, Collection<int, KillZoneEnemyForces>>
     */
    public function getEnemyForcesPerKillZoneForRoutes(Collection $dungeonRoutes): Collection;
}
