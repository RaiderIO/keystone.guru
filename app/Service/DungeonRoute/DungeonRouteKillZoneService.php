<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use Illuminate\Support\Collection;

class DungeonRouteKillZoneService implements DungeonRouteKillZoneServiceInterface
{
    public function __construct(
        private readonly DungeonRouteRepositoryInterface $dungeonRouteRepository,
    ) {
    }

    /**
     * @return Collection<int, KillZoneEnemyForces>
     */
    public function getEnemyForcesPerKillZone(DungeonRoute $dungeonRoute): Collection
    {
        return $this->dungeonRouteRepository->getEnemyForcesPerKillZone($dungeonRoute);
    }
}
