<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteChange;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteChangeRepositoryInterface;

class DungeonRouteChangeRepository extends DatabaseRepository implements DungeonRouteChangeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteChange::class);
    }
}
