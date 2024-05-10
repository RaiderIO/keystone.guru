<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteEnemyRaidMarkerRepositoryInterface;

class DungeonRouteEnemyRaidMarkerRepository extends DatabaseRepository implements DungeonRouteEnemyRaidMarkerRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteEnemyRaidMarker::class);
    }
}
