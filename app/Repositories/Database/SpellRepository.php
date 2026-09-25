<?php

namespace App\Repositories\Database;

use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\CombatLog\DataExtractors\Characteristics\CharacteristicEvidenceRule;
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
    /**
     * @param  Collection<int, int>|Collection<int, Spell> $spellIds
     * @return Collection<int, Spell>
     */
    public function findAllById(Collection $spellIds): Collection
    {
        return Spell::query()
            ->whereIn('id', $spellIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * The effects come along because {@see CharacteristicEvidenceRule} judges every one of these spells on
     * them, and the extraction pipeline holds this catalog for the whole run.
     *
     * @return Collection<int, Spell>
     */
    public function getAllWithCharacteristic(): Collection
    {
        return Spell::query()
            ->whereNotNull('characteristic_id')
            // A PvP talent cannot be pressed in a dungeon, so an observation attributed to one is a
            // mis-parse rather than evidence
            ->where('is_pvp_talent', false)
            ->with('spellEffects')
            ->get()
            ->keyBy('id');
    }

    /**
     * @return Collection<int, Spell>
     */
    public function getAllKeyedWithSpellDungeons(): Collection
    {
        return Spell::with('spellDungeons')
            ->get()
            ->keyBy('id');
    }

    /**
     * @return Collection<int, int>
     */
    public function getHiddenOnMapSpellIds(): Collection
    {
        return Spell::query()
            ->where('hidden_on_map', true)
            ->pluck('id');
    }
}
