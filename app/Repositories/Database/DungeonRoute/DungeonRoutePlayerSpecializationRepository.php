<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerSpecializationRepositoryInterface;

class DungeonRoutePlayerSpecializationRepository extends DatabaseRepository implements DungeonRoutePlayerSpecializationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRoutePlayerSpecialization::class);
    }
}
