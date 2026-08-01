<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRepositoryInterface;

class DungeonRouteCollectionRepository extends DatabaseRepository implements DungeonRouteCollectionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteCollection::class);
    }
}
