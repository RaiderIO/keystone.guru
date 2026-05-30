<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcDungeon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcDungeonRepositoryInterface;

class NpcDungeonRepository extends DatabaseRepository implements NpcDungeonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcDungeon::class);
    }
}
