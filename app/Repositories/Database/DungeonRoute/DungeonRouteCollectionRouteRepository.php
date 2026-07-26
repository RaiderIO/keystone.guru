<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRouteRepositoryInterface;

class DungeonRouteCollectionRouteRepository extends DatabaseRepository implements DungeonRouteCollectionRouteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteCollectionRoute::class);
    }
}
