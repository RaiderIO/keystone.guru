<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface DungeonRouteComparisonServiceInterface
{
    public function findSimilarRoutes(DungeonRoute $route, int $limit = 5): Collection;
}
