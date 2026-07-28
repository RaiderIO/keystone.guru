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
}
