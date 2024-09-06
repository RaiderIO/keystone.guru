<?php

namespace App\Repositories\Database;

use App\Models\Dungeon;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use Illuminate\Support\Collection;

class DungeonRepository extends DatabaseRepository implements DungeonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Dungeon::class);
    }

    public function getAllMapIds(): Collection
    {
        return Dungeon::get('map_id')->pluck('map_id')->unique();
    }
}
