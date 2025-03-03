<?php

namespace App\Repositories\Database;

use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use Illuminate\Support\Collection;

class SpellRepository extends DatabaseRepository implements SpellRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Spell::class);
    }

    public function getMissingSpellIds(): array
    {
        return NpcSpell::query()
            ->leftJoin('spells', 'npc_spells.spell_id', '=', 'spells.id')
            ->whereNull('spells.id') // Ensure spell doesn't exist in spells table
            ->distinct()
            ->pluck('npc_spells.spell_id')
            ->toArray();
    }

    public function findAllById(Collection $spellIds): Collection
    {
        return Spell::query()
            ->whereIn('id', $spellIds)
            ->get()
            ->keyBy('id');
    }
}
