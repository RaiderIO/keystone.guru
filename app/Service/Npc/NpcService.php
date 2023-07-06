<?php

namespace App\Service\Npc;

use App\Models\Dungeon;
use App\Models\Npc;
use Illuminate\Support\Collection;

class NpcService implements NpcServiceInterface
{

    /**
     * @inheritDoc
     */
    public function getNpcsForDropdown(Dungeon $dungeon, bool $includeAllDungeonsNpcs = false): Collection
    {
        $npcIds = collect([
            __($dungeon->name) => Npc::whereIn('dungeon_id', [$dungeon->id])
                ->get(['name', 'id'])
                ->pluck('name', 'id')
                ->mapWithKeys(function ($name, $id) {
                    return [$id => sprintf('%s (%d)', $name, $id)];
                }),
        ]);

        if ($includeAllDungeonsNpcs) {
            $allDungeonNpcs = collect([
                __('services.npcservice.all_dungeons') => Npc::whereIn('dungeon_id', [-1])
                    ->get(['name', 'id'])
                    ->pluck('name', 'id')
                    ->mapWithKeys(function ($name, $id) {
                        return [$id => sprintf('%s (%d)', $name, $id)];
                    }),
            ]);

            $npcIds = $npcIds->merge($allDungeonNpcs);
        }

        return $npcIds;
    }
}
