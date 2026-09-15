<?php

namespace App\Repositories\Database\Npc;

use App\Models\Dungeon;
use App\Models\Npc\NpcDungeon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcDungeonRepositoryInterface;
use Illuminate\Support\Collection;

class NpcDungeonRepository extends DatabaseRepository implements NpcDungeonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcDungeon::class);
    }

    /**
     * @return Collection<int, int>
     */
    public function getNpcIdsByDungeon(Dungeon $dungeon): Collection
    {
        return NpcDungeon::query()
            ->where('dungeon_id', $dungeon->id)
            ->pluck('npc_id');
    }
}
