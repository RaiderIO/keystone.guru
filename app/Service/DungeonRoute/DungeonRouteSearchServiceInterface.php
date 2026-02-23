<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\Dtos\DungeonRouteSearchFilter;
use Illuminate\Support\Collection;

interface DungeonRouteSearchServiceInterface
{
    public function findSimilarRoutes(DungeonRoute $route, int $limit = 5): Collection;

    public function search(DungeonRouteSearchFilter $filter): Collection;
}
