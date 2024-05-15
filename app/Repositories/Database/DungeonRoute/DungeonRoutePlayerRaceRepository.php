<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerRaceRepositoryInterface;

class DungeonRoutePlayerRaceRepository extends DatabaseRepository implements DungeonRoutePlayerRaceRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRoutePlayerRace::class);
    }
}
