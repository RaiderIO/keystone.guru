<?php

namespace App\Service\Npc;

use App\Models\Npc\Npc;
use Illuminate\Database\Query\JoinClause;
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
            $npcIds->put(
                __($dungeon->name),
                Npc::selectRaw('npcs.id, npc_name_translations.translation as name')
                    ->leftJoin('translations as npc_name_translations', function (JoinClause $clause) {
                        $clause->on('npc_name_translations.key', '=', 'npcs.name')
                            ->where('npc_name_translations.locale', '=', 'en_US');
                    })
                    ->join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
                    ->where('npc_dungeons.dungeon_id', $dungeon->id)
                    ->get([
                        'name',
                        'id',
                    ])
                    ->mapWithKeys(static fn(Npc $npc) => [
                        $npc->id => sprintf('%s (%d)', $npc->translated_name ?? $npc->name, $npc->id),
                    ]),
            );
        }

        return $npcIds;
    }
}
