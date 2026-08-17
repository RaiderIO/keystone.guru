<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Models\CharacterClass;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
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

        // Alphabetically by the name the player actually reads: they look up the spell they are about
        // to press. Spell id - the order this arrives in otherwise - reshuffles the whole table every
        // time the game adds an ability, and grouping by characteristic would order the rows by a
        // taxonomy nobody thinks in.
        $spells = self::sortByTranslatedName(
            Spell::query()
                ->where('category', sprintf('spellcategory.%s', $characterClass->key))
                ->whereNotNull('characteristic_id')
                ->when($mappingVersion !== null, static fn($q) => $q->where('game_version_id', $mappingVersion->game_version_id))
                ->with('characteristic')
                ->get(),
        );

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
                // classification/type/characteristics/npcHealths are what the NPC links' hover
                // tooltips read (#4096)
                ->with(['classification', 'type', 'characteristics', 'npcHealths'])
                ->distinct()
                ->orderBy('npcs.id')
                ->get()
                ->groupBy('characteristic_id');
        }

        return view('compendium.class.show', [
            'characterClass'                => $characterClass,
            'contextDungeon'                => $dungeon,
            'spells'                        => $spells,
            'npcsByCharacteristicId'        => $npcsByCharacteristicId,
            'notableNpcsByCharacteristicId' => $this->getNotableNpcsByCharacteristicId($characteristicIds, $npcsByCharacteristicId),
            'counterSections'               => $this->getCounterSections($characterClass, $dungeon, $mappingVersion),
            'reflectSection'                => $this->getReflectSection($characterClass, $dungeon, $mappingVersion),
            // HeaderComposer only injects this into the header view itself - the dungeon context
            // links this page overrides are built in the view, so it needs its own copy
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion(),
        ]);
    }

    /**
     * Per characteristic, the NPCs that behave *differently from what a player already assumes* -
     * the only part of this data that is worth the screen space.
     *
     * Crowd control working on trash and failing on bosses is assumed knowledge, so listing it is
     * noise. Two exceptions to that assumption are worth knowing before a pull, and they are what
     * this returns:
     *
     * - `noEffect`: any non-boss NPC (normal, elite or rare) that resisted, i.e. was never observed
     *               affected. Normal trash is exactly as "everyone assumes it's affected by
     *               everything" as an elite or a rare, so a real immunity there is just as surprising.
     * - `worksOn`:  bosses that *were* observed affected - the arena-style fights where crowd
     *               control unexpectedly lands.
     *
     * The two groups are deliberately keyed off the NPC's classification rather than off list
     * lengths, so a given NPC can only ever appear in the direction that is surprising for what it
     * is. That also keeps each claim honest, because the two directions rest on very different
     * evidence:
     *
     * `worksOn` is positive evidence - `npc_characteristics` recorded the aura landing - so it needs
     * no qualification. `noEffect` is the absence of a recording, which is only meaningful for an
     * NPC we have *other* observations for; there is no NPC immunity table to consult. So the
     * unaffected side is drawn from a universe of NPCs already observed being affected by at least
     * one characteristic of this class (including this one's own row), and never from every NPC in
     * the dungeon.
     *
     * Taunt still contributes to that universe like any other characteristic - an elite/rare only
     * ever observed being taunted must stay eligible to appear in another characteristic's `noEffect`
     * row (and, if it was never observed taunted itself, in Taunt's own row). What taunt does *not*
     * do is get treated as stronger evidence than that: it is a tanking mechanic that lands on nearly
     * every tauntable trash mob, so "we have seen this NPC taunted" says nothing about whether it can
     * be stunned beyond making the NPC eligible for the universe in the first place, same as any
     * other characteristic's observation would.
     *
     * @param  Collection<int, int>                                                                  $characteristicIds
     * @param  Collection<int, Collection<int, Npc>>                                                 $npcsByCharacteristicId
     * @return Collection<int, array{noEffect: Collection<int, Npc>, worksOn: Collection<int, Npc>}>
     */
    private function getNotableNpcsByCharacteristicId(Collection $characteristicIds, Collection $npcsByCharacteristicId): Collection
    {
        // The same Npc may be hydrated once per characteristic - any of them will do, since only the
        // npc itself is rendered and never the characteristic_id that was selected alongside it.
        // reject() and not except(): on an Eloquent collection except() filters by model key

        /** @var Collection<int, Npc> $npcsInUniverse */
        $npcsInUniverse = $npcsByCharacteristicId
            ->flatten(1)
            ->unique('id')
            // Bosses are only ever reported through worksOn. Letting one into the unaffected universe
            // would turn a single observation ("this boss was stunned") into a claim on every other
            // row ("...so it is immune to fear"), which is the exact inference this method avoids.
            ->reject(static fn(Npc $npc) => $npc->isBoss())
            // Every non-boss classification (normal, elite, rare) is fair game: a normal-classification
            // trash mob is exactly as "everyone assumes it's affected by everything" as an elite or a
            // rare, so a real immunity there is just as surprising and worth reporting. `classification_id`
            // is NOT NULL and boss/finalboss are already rejected above, so this currently admits
            // everything left - it stays an explicit allowlist rather than a bare "not a boss" check so a
            // future classification does not silently join the universe.
            ->filter(static fn(Npc $npc) => in_array($npc->classification_id, [
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE],
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE],
            ], true));

        return $characteristicIds->mapWithKeys(static function (int $characteristicId) use ($npcsByCharacteristicId, $npcsInUniverse) {
            $affectedNpcs = $npcsByCharacteristicId->get($characteristicId, collect());

            return [
                $characteristicId => [
                    'noEffect' => self::sortByTranslatedName($npcsInUniverse->whereNotIn('id', $affectedNpcs->pluck('id'))),
                    'worksOn'  => self::sortByTranslatedName($affectedNpcs->filter(static fn(Npc $npc) => $npc->isBoss())),
                ],
            ];
        });
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
            // classification/type/characteristics/npcHealths are what the NPC links' hover tooltips
            // read (#4096)
            ->with(['classification', 'type', 'characteristics', 'npcHealths'])
            ->distinct()
            ->get()
            ->groupBy('spell_id');

        return $npcsBySpellId;
    }

    /**
     * Sorts on the *rendered* name. Both tables hold a translation key rather than a name
     * (`npcs.160495`, `spells.46968`), so an `orderBy('name')` in the query would sort those keys
     * lexicographically - putting `spells.103828` ahead of `spells.1715` ahead of `spells.355`,
     * which is neither id order nor alphabetical.
     *
     * @template TModel of Npc|Spell
     *
     * @param  Collection<int, TModel> $models
     * @return Collection<int, TModel>
     */
    private static function sortByTranslatedName(Collection $models): Collection
    {
        return $models->sortBy(static fn(Npc|Spell $model) => __($model->name))->values();
    }
}
