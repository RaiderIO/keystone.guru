<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use Illuminate\Support\Collection;

interface DungeonRouteSearchServiceInterface
{
    /**
     * @return Collection<int, mixed>
     */
    public function findSimilarRoutes(DungeonRoute $route, int $limit = 5): Collection;

    /**
     * @return Collection<int, mixed>
     */
    public function search(DungeonRouteSearchFilter $filter): Collection;
}
