<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Database\DungeonRoute\Dtos\SimilarDungeonRoute;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use Illuminate\Support\Collection;

interface DungeonRouteSearchServiceInterface
{
    /**
     * @return Collection<int, SimilarDungeonRoute>
     */
    public function findSimilarRoutes(DungeonRoute $route, int $limit = 5): Collection;

    /**
     * @return Collection<int, DungeonRoute>
     */
    public function search(DungeonRouteSearchFilter $filter): Collection;
}
