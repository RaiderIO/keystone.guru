<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use Illuminate\Support\Collection;

class DungeonRouteComparisonService implements DungeonRouteComparisonServiceInterface
{
    public function __construct(
        private readonly DungeonRouteRepositoryInterface $dungeonRouteRepository,
    ) {
    }

    public function findSimilarRoutes(
        DungeonRoute $route,
        int          $limit = 5,
    ): Collection {
        return $this->dungeonRouteRepository->findSimilarRoutes($route, $limit);
    }
}
