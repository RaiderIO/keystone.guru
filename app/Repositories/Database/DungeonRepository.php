<?php

namespace App\Repositories\Database;

use App\Models\Dungeon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRepositoryInterface;

class DungeonRepository extends DatabaseRepository implements DungeonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Dungeon::class);
    }
}
