<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Models\CharacterClass;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Service\CombatLog\DataExtractors\SpellCounters\SpellCounterDefinitionInterface;
use App\Service\CombatLog\DataExtractors\SpellCounters\SpellCounterDefinitions;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ClassCompendiumController extends Controller
{
    public function index(): View
    {
        return view('compendium.class.index', [
            'characterClasses' => CharacterClass::orderBy('name')->get(),
        ]);
    }

    public function show(CharacterClass $characterClass): View
    {
        $dungeon        = Dungeon::getUserOrDefaultDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        $spells = Spell::query()
            ->where('category', sprintf('spellcategory.%s', $characterClass->key))
            ->whereNotNull('characteristic_id')
            ->when($mappingVersion !== null, static fn($q) => $q->where('game_version_id', $mappingVersion->game_version_id))
            ->with('characteristic')
            ->get();

        $characteristicIds = $spells->pluck('characteristic_id')->unique()->filter();

        /** @var Collection<int, Collection<int, Npc>> $npcsByCharacteristicId */
        $npcsByCharacteristicId = collect();

        if ($characteristicIds->isNotEmpty()) {
            $npcsByCharacteristicId = Npc::query()
                ->join('npc_characteristics', 'npc_characteristics.npc_id', '=', 'npcs.id')
                ->join('enemies', 'enemies.npc_id', '=', 'npcs.id')
                ->join('mapping_versions', 'enemies.mapping_version_id', '=', 'mapping_versions.id')
                ->where('mapping_versions.dungeon_id', $dungeon->id)
                ->when($mappingVersion !== null, static fn($q) => $q->where('mapping_versions.id', $mappingVersion->id))
                ->whereIn('npc_characteristics.characteristic_id', $characteristicIds)
                ->select('npcs.*', 'npc_characteristics.characteristic_id')
                ->with('classification')
                ->distinct()
                ->get()
                ->groupBy('characteristic_id');
        }

        return view('compendium.class.show', [
            'characterClass'         => $characterClass,
            'contextDungeon'         => $dungeon,
            'spells'                 => $spells,
            'npcsByCharacteristicId' => $npcsByCharacteristicId,
            'counterSections'        => $this->getCounterSections($characterClass, $dungeon, $mappingVersion),
        ]);
    }

    /**
     * @return array<int, array{
     *     definition: SpellCounterDefinitionInterface,
     *     raceName: string|null,
     *     spells: Collection<int, Spell>,
     *     npcsBySpellId: Collection<int, Collection<int, Npc>>,
     * }>
     */
    private function getCounterSections(CharacterClass $characterClass, Dungeon $dungeon, ?MappingVersion $mappingVersion): array
    {
        $classRaceKeys = $characterClass->races->pluck('key');

        $definitions = SpellCounterDefinitions::all()->filter(
            static fn(SpellCounterDefinitionInterface $definition) => $definition->getCharacterClassKey() === $characterClass->key
                || ($definition->getCharacterRaceKey() !== null && $classRaceKeys->contains($definition->getCharacterRaceKey())),
        );

        $result = [];

        foreach ($definitions as $definition) {
            $bit = $definition->getCounterBit();

            $spells = Spell::query()
                ->whereRaw(sprintf('counters_mask & %d != 0', $bit))
                ->when($mappingVersion !== null, static fn($q) => $q->where('game_version_id', $mappingVersion->game_version_id))
                ->get();

            /** @var Collection<int, Collection<int, Npc>> $npcsBySpellId */
            $npcsBySpellId = collect();

            if ($spells->isNotEmpty()) {
                $npcsBySpellId = Npc::query()
                    ->join('npc_spells', 'npc_spells.npc_id', '=', 'npcs.id')
                    ->join('enemies', 'enemies.npc_id', '=', 'npcs.id')
                    ->join('mapping_versions', 'enemies.mapping_version_id', '=', 'mapping_versions.id')
                    ->where('mapping_versions.dungeon_id', $dungeon->id)
                    ->when($mappingVersion !== null, static fn($q) => $q->where('mapping_versions.id', $mappingVersion->id))
                    ->whereIn('npc_spells.spell_id', $spells->pluck('id'))
                    ->select('npcs.*', 'npc_spells.spell_id')
                    ->distinct()
                    ->get()
                    ->groupBy('spell_id');
            }

            $raceKey  = $definition->getCharacterRaceKey();
            $raceName = $raceKey !== null ? $characterClass->races->firstWhere('key', $raceKey)?->name : null;

            $result[$bit] = [
                'definition'    => $definition,
                'raceName'      => $raceName,
                'spells'        => $spells,
                'npcsBySpellId' => $npcsBySpellId,
            ];
        }

        return $result;
    }
}
