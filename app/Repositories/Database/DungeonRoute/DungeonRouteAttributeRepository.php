<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAttributeRepositoryInterface;

class DungeonRouteAttributeRepository extends DatabaseRepository implements DungeonRouteAttributeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteAttribute::class);
    }
}
