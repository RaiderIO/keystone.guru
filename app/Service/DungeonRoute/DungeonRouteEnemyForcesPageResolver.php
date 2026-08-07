<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use Illuminate\Support\Collection;

/**
 * Resolves per-pull enemy forces for the routes on a single leaderboard page (cardrow/cardhero),
 * batching them into a single grouped query instead of one query per card. The batched query is
 * only fired on the first call to {@see self::forRoute()} (i.e. the first cache miss encountered
 * while rendering the page), so a fully-cached page still fires zero forces queries.
 */
class DungeonRouteEnemyForcesPageResolver
{
    /** @var Collection<int, Collection<int, KillZoneEnemyForces>>|null */
    private ?Collection $forcesByRouteId = null;

    /**
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    public function __construct(
        private readonly DungeonRouteKillZoneServiceInterface $dungeonRouteKillZoneService,
        private readonly Collection                           $dungeonRoutes,
    ) {
    }

    /**
     * @return Collection<int, KillZoneEnemyForces>
     */
    public function forRoute(DungeonRoute $dungeonRoute): Collection
    {
        $this->forcesByRouteId ??= $this->dungeonRouteKillZoneService->getEnemyForcesPerKillZoneForRoutes($this->dungeonRoutes);

        return $this->forcesByRouteId->get($dungeonRoute->id, collect());
    }
}
