<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use Illuminate\Support\Collection;

class DungeonRouteSearchService implements DungeonRouteSearchServiceInterface
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

    public function search(DungeonRouteSearchFilter $filter): Collection
    {
        // TODO: Implement search() method.
    }
}
