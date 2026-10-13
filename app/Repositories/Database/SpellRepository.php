<?php

namespace App\Repositories\Database;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellMissType;
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

    public function findAllByIdWithTooltipRelations(Collection $spellIds): Collection
    {
        return Spell::query()
            ->with('descriptionTranslation')
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

    public function getCharacteristicSpellsForCategory(string $category, ?MappingVersion $mappingVersion): Collection
    {
        return Spell::query()
            ->where('category', $category)
            ->whereNotNull('characteristic_id')
            // A PvP talent is unavailable in a dungeon, so listing it as crowd control for one is a
            // lie the player only finds out about mid-pull
            ->where('is_pvp_talent', false)
            // What the spell links' hover tooltips read in a non-English locale
            ->with('descriptionTranslation')
            ->when($mappingVersion !== null, static fn($q) => $q->where('game_version_id', $mappingVersion->game_version_id))
            ->with('characteristic')
            ->get();
    }

    public function getVisibleSpellsByCounterBitForDungeon(int $counterBit, Dungeon $dungeon, ?MappingVersion $mappingVersion): Collection
    {
        return Spell::query()
            ->visible()
            // What the spell links' hover tooltips read in a non-English locale
            ->with('descriptionTranslation')
            ->whereRaw(sprintf('counters_mask & %d != 0', $counterBit))
            ->when($mappingVersion !== null, static fn($q) => $q->where('game_version_id', $mappingVersion->game_version_id))
            ->whereIn('id', static function ($query) use ($dungeon): void {
                $query->select('spell_id')->from('spell_dungeons')->where('dungeon_id', $dungeon->id);
            })
            ->get();
    }

    public function getVisibleReflectedSpellsForDungeon(Dungeon $dungeon, ?MappingVersion $mappingVersion): Collection
    {
        return Spell::query()
            ->visible()
            ->with('descriptionTranslation')
            ->whereRaw('miss_types_mask & ? != 0', [SpellMissType::Reflect->value])
            ->when($mappingVersion !== null, static fn($q) => $q->where('game_version_id', $mappingVersion->game_version_id))
            ->whereIn('id', static function ($query) use ($dungeon): void {
                $query->select('spell_id')->from('spell_dungeons')->where('dungeon_id', $dungeon->id);
            })
            ->get();
    }
}
