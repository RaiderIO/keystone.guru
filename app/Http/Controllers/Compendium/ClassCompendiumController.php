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
use App\Service\Dungeon\DungeonServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClassCompendiumController extends Controller
{
    /**
     * Classes that have a spell reflect ability, mapped to the icon shown in the section header.
     * Warrior's Spell Reflection is the only one that matters in Mythic+ - the Warlock's Nether Ward
     * is PvP only. Not scoped per game version: on a game version where the class has no reflect the
     * section simply lists nothing, which reads the same as any other dungeon without reflect data.
     */
    private const array SPELL_REFLECT_CLASS_ICONS = [
        CharacterClass::CHARACTER_CLASS_WARRIOR => 'ability_warrior_shieldreflection',
    ];

    public function index(): View
    {
        return view('compendium.class.index', [
            'characterClasses' => CharacterClass::orderBy('name')->get(),
        ]);
    }

    /**
     * The class page without a dungeon in the URL - bounces to the canonical URL of the visitor's
     * context dungeon.
     *
     * Deliberately a 302: the target depends on the visitor's own context dungeon, so a permanent
     * redirect would get cached and pin one dungeon into every later visit.
     */
    public function show(CharacterClass $characterClass): RedirectResponse
    {
        return redirect()->route('compendium.class.show.dungeon', [
            'characterClass' => $characterClass,
            'dungeon'        => Dungeon::getUserOrDefaultDungeon(),
        ]);
    }

    /**
     * Parameters follow the URL's order (/compendium/dungeon/{dungeon}/class/{characterClass}) - a
     * route registered with first-class callable syntax binds them positionally, not by name.
     */
    public function showDungeon(Dungeon $dungeon, CharacterClass $characterClass, DungeonServiceInterface $dungeonService): View
    {
        // The URL is the source of truth for which dungeon is being viewed - make it the context
        // dungeon as well, so the header's dungeon selection follows along (as on explore/heatmap)
        $dungeonService->setDungeonContext($dungeon, Auth::user());

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
                ->orderBy('npcs.id')
                ->get()
                ->groupBy('characteristic_id');
        }

        return view('compendium.class.show', [
            'characterClass'                   => $characterClass,
            'contextDungeon'                   => $dungeon,
            'spells'                           => $spells,
            'npcsByCharacteristicId'           => $npcsByCharacteristicId,
            'unaffectedNpcsByCharacteristicId' => $this->getUnaffectedNpcsByCharacteristicId($characteristicIds, $npcsByCharacteristicId),
            'counterSections'                  => $this->getCounterSections($characterClass, $dungeon, $mappingVersion),
            'reflectSection'                   => $this->getReflectSection($characterClass, $dungeon, $mappingVersion),
            // HeaderComposer only injects this into the header view itself - the dungeon context
            // links this page overrides are built in the view, so it needs its own copy
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion(),
        ]);
    }

    /**
     * The complement of the affected-NPC lists: per characteristic, the dungeon's NPCs that were
     * *not* observed being affected by it.
     *
     * `npc_characteristics` only ever records positive evidence - there is no NPC immunity table -
     * so the complement is taken against the NPCs affected by at least one of *this class's*
     * characteristics rather than against every NPC in the dungeon. An NPC that only ever shows up
     * with an unrelated characteristic (taunt is applied to nearly every tauntable trash mob) is no
     * evidence at all that this class's crowd control fails on it.
     *
     * @param  Collection<int, int>                  $characteristicIds
     * @param  Collection<int, Collection<int, Npc>> $npcsByCharacteristicId
     * @return Collection<int, Collection<int, Npc>>
     */
    private function getUnaffectedNpcsByCharacteristicId(Collection $characteristicIds, Collection $npcsByCharacteristicId): Collection
    {
        // The same Npc may be hydrated once per characteristic - any of them will do, since only the
        // npc itself is rendered and never the characteristic_id that was selected alongside it
        /** @var Collection<int, Npc> $npcsInUniverse */
        $npcsInUniverse = $npcsByCharacteristicId->flatten(1)->unique('id');

        return $characteristicIds->mapWithKeys(static fn(int $characteristicId) => [
            $characteristicId => $npcsInUniverse
                ->whereNotIn('id', $npcsByCharacteristicId->get($characteristicId, collect())->pluck('id'))
                ->values(),
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

            // Scoped to the context dungeon so the listed spells match the section's "for this dungeon" framing
            $spells = Spell::query()
                ->visible()
                ->whereRaw(sprintf('counters_mask & %d != 0', $bit))
                ->when($mappingVersion !== null, static fn($q) => $q->where('game_version_id', $mappingVersion->game_version_id))
                ->whereIn('id', static function ($query) use ($dungeon): void {
                    $query->select('spell_id')->from('spell_dungeons')->where('dungeon_id', $dungeon->id);
                })
                ->get();

            $npcsBySpellId = $this->getNpcsBySpellId($dungeon, $mappingVersion, $spells);

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

    /**
     * NPC spells that have been observed being reflected - only relevant for classes that actually
     * have a reflect ability, hence the null for everyone else.
     *
     * @return array{
     *     iconName: string,
     *     spells: Collection<int, Spell>,
     *     npcsBySpellId: Collection<int, Collection<int, Npc>>,
     * }|null
     */
    private function getReflectSection(CharacterClass $characterClass, Dungeon $dungeon, ?MappingVersion $mappingVersion): ?array
    {
        $iconName = self::SPELL_REFLECT_CLASS_ICONS[$characterClass->key] ?? null;

        if ($iconName === null) {
            return null;
        }

        // Scoped to the context dungeon so the listed spells match the section's "for this dungeon" framing
        $spells = Spell::query()
            ->visible()
            ->whereRaw('miss_types_mask & ? != 0', [Spell::MISS_TYPE_REFLECT])
            ->when($mappingVersion !== null, static fn($q) => $q->where('game_version_id', $mappingVersion->game_version_id))
            ->whereIn('id', static function ($query) use ($dungeon): void {
                $query->select('spell_id')->from('spell_dungeons')->where('dungeon_id', $dungeon->id);
            })
            ->get();

        return [
            'iconName'      => $iconName,
            'spells'        => $spells,
            'npcsBySpellId' => $this->getNpcsBySpellId($dungeon, $mappingVersion, $spells),
        ];
    }

    /**
     * The NPCs in the given dungeon that cast any of the given spells, grouped by spell id.
     *
     * @param  Collection<int, Spell>                $spells
     * @return Collection<int, Collection<int, Npc>>
     */
    private function getNpcsBySpellId(Dungeon $dungeon, ?MappingVersion $mappingVersion, Collection $spells): Collection
    {
        if ($spells->isEmpty()) {
            return collect();
        }

        /** @var Collection<int, Collection<int, Npc>> $npcsBySpellId */
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

        return $npcsBySpellId;
    }
}
