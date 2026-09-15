<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcSpell;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcSpellRepositoryInterface;
use Illuminate\Support\Collection;

class NpcSpellRepository extends DatabaseRepository implements NpcSpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcSpell::class);
    }

    /**
     * @param  Collection<int, int> $npcIds
     * @return Collection<int, int>
     */
    public function getSpellIdsByNpcIds(Collection $npcIds): Collection
    {
        return NpcSpell::query()
            ->whereIn('npc_id', $npcIds)
            ->pluck('spell_id')
            ->unique()
            ->values();
    }
}
