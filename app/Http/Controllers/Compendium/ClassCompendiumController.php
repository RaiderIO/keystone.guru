<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Models\CharacterClass;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
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

        /** @var Collection<int, Collection<Npc>> $npcsByCharacteristicId */
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
        ]);
    }
}
