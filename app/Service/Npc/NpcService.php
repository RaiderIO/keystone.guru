<?php

namespace App\Service\Npc;

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use Illuminate\Support\Collection;

class NpcService implements NpcServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getNpcsForDropdown(Collection $dungeons): Collection
    {
        $npcIds = collect();

        foreach ($dungeons as $dungeon) {
            $npcIds->push([
                __($dungeon->name) => Npc::select('npcs.*')
                    ->join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
                    ->where('npc_dungeons.dungeon_id', $dungeon->id)
                    ->get(['name', 'id'])
                    ->pluck('name', 'id')
                    ->mapWithKeys(static fn($name, $id) => [$id => sprintf('%s (%d)', $name, $id)]),
            ]);
        }

        return $npcIds;
    }
}
