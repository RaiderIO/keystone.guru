<?php

namespace App\Repositories\Database;

use App\Models\DungeonFloorSwitchMarker;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonFloorSwitchMarkerRepositoryInterface;

class DungeonFloorSwitchMarkerRepository extends DatabaseRepository implements DungeonFloorSwitchMarkerRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonFloorSwitchMarker::class);
    }
}
