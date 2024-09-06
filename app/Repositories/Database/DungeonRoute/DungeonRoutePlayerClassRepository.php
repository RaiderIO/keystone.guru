<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerClassRepositoryInterface;

class DungeonRoutePlayerClassRepository extends DatabaseRepository implements DungeonRoutePlayerClassRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRoutePlayerClass::class);
    }
}
