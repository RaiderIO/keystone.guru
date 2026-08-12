<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\CharacterClass;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class ClassCompendiumControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::findOrFail(1));
        Feature::define(NpcCompendium::class, true);
    }

    #[Test]
    public function index_givenFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('compendium.class.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('compendium.class.index'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenFeatureEnabled_displaysAllClasses(): void
    {
        // Act
        $response = $this->get(route('compendium.class.index'));

        // Assert
        $response->assertOk();
        foreach (CharacterClass::all() as $characterClass) {
            $response->assertSeeText(__($characterClass->name));
        }
    }

    #[Test]
    public function show_givenValidClass_returnsOk(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show', $characterClass));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function show_givenInvalidClass_returnsNotFound(): void
    {
        // Act
        $response = $this->get(route('compendium.class.show', ['characterClass' => 'invalid_class']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function show_givenValidClass_displaysClassNameAndContextDungeon(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();
        $dungeon        = Dungeon::getUserOrDefaultDungeon();

        // Act
        $response = $this->get(route('compendium.class.show', $characterClass));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__($characterClass->name));
        $response->assertSeeText(__($dungeon->name));
    }

    #[Test]
    public function show_givenRogueClass_displaysVanishAndShadowmeldCounterSections(): void
    {
        // Arrange — Rogue has Vanish as a class ability, and can be a Night Elf (Shadowmeld racial)
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_ROGUE)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show', $characterClass));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__('spellcounters.vanish'));
        $response->assertSeeText(__('spellcounters.shadowmeld'));
    }

    #[Test]
    public function show_givenPaladinClass_doesNotDisplayVanishOrShadowmeldCounterSections(): void
    {
        // Arrange — Vanish is Rogue-only, so Paladin must not see it. Paladin also cannot be a Night
        // Elf, so the Shadowmeld racial counter must not appear either.
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_PALADIN)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show', $characterClass));

        // Assert
        $response->assertOk();
        $response->assertDontSeeText(__('spellcounters.vanish'));
        $response->assertDontSeeText(__('spellcounters.shadowmeld'));
    }

    #[Test]
    public function show_givenWarriorClass_displaysReflectSection(): void
    {
        // Arrange - Spell Reflection is the only reflect ability in retail Mythic+
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_WARRIOR)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show', $characterClass));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__('view_compendium.class.show.reflect.title'));
    }

    #[Test]
    public function show_givenPaladinClass_doesNotDisplayReflectSection(): void
    {
        // Arrange - Paladin has no reflect ability, so the section must not be rendered at all
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_PALADIN)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show', $characterClass));

        // Assert
        $response->assertOk();
        $response->assertDontSeeText(__('view_compendium.class.show.reflect.title'));
    }

    #[Test]
    public function show_givenReflectedSpellInDungeon_displaysSpellAndCastingNpc(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_WARRIOR)->firstOrFail();

        // Find a Retail dungeon whose current mapping version has enemies AND an NPC without any
        // characteristic or spell of its own, so the NPC cannot also be listed by the CC table or a
        // counter section and make the assertion below pass for the wrong reason. The NPC requirement
        // has to be part of the dungeon selection rather than a follow-up query: an MDT mapping import
        // can give every NPC in a given dungeon a spell (MDT 6.2.1 did exactly that to the Midnight
        // dungeons, see #3980), which leaves that dungeon with no candidate at all.
        $defaultGameVersion               = GameVersion::getDefaultGameVersion();
        [$dungeon, $mappingVersion, $npc] = $this->findDungeon(
            challengeMode: true,
            minEnemies:    1,
            gameVersion:   $defaultGameVersion,
            shuffle:       false,
            constraint:    static fn(Builder $query) => $query
                ->where('challenge_mode_id', '>', 0)
                ->orderByDesc('dungeons.id'),
            resolve:       static fn(Dungeon $dungeon, MappingVersion $mappingVersion) => Npc::query()
                ->join('enemies', 'enemies.npc_id', '=', 'npcs.id')
                ->where('enemies.mapping_version_id', $mappingVersion->id)
                ->whereNotIn('npcs.id', static function ($sub): void {
                    $sub->select('npc_id')->from('npc_characteristics');
                })
                ->whereNotIn('npcs.id', static function ($sub): void {
                    $sub->select('npc_id')->from('npc_spells');
                })
                ->select('npcs.*')
                ->first(),
        );
        $this->assertNotNull($npc);

        // Deliberately without a characteristic or counter bit, so the spell cannot also show up in
        // the CC table or a counter section and make the assertions below pass for the wrong reason
        $spell = Spell::query()
            ->where('game_version_id', $mappingVersion->game_version_id)
            ->where('hidden_on_map', false)
            ->whereNull('characteristic_id')
            ->where('counters_mask', 0)
            ->whereRaw('miss_types_mask & ? = 0', [Spell::MISS_TYPE_REFLECT])
            ->first();
        $this->assertNotNull($spell);
        $originalMissTypesMask = $spell->miss_types_mask;

        // Set the user's context dungeon to the one with enemies
        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $dungeon->id;
        $user->save();
        $user = $user->fresh();

        $spellDungeon = null;
        $npcSpell     = null;

        try {
            $spell->miss_types_mask = $originalMissTypesMask | Spell::MISS_TYPE_REFLECT;
            $spell->save();

            $spellDungeon = SpellDungeon::create([
                'spell_id'   => $spell->id,
                'dungeon_id' => $dungeon->id,
            ]);
            $npcSpell = NpcSpell::create([
                'npc_id'   => $npc->id,
                'spell_id' => $spell->id,
            ]);

            // Act
            $response = $this->actingAs($user)->get(route('compendium.class.show', $characterClass));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.class.show.reflect.title'));
            $response->assertSeeText(__($spell->name));
            $response->assertSeeText(__($npc->name));
        } finally {
            $npcSpell?->delete();
            $spellDungeon?->delete();

            $spell->miss_types_mask = $originalMissTypesMask;
            $spell->save();

            $user->dungeon_id = $originalDungeonId;
            $user->save();
        }
    }

    #[Test]
    public function show_givenNpcWithMatchingCharacteristic_displaysNpcName(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();

        // Find a dungeon whose current mapping version has enemies for the Retail game version
        $defaultGameVersion = GameVersion::getDefaultGameVersion();
        $dungeon            = $this->getDungeonWithCurrentMappingVersionWithEnemies($defaultGameVersion);
        $mappingVersion     = $dungeon->getCurrentMappingVersionForGameVersion($defaultGameVersion);
        $this->assertNotNull($mappingVersion);

        $spell = Spell::where('category', sprintf('spellcategory.%s', $characterClass->key))
            ->whereNotNull('characteristic_id')
            ->where('game_version_id', $mappingVersion->game_version_id)
            ->first();
        $this->assertNotNull($spell);

        // Find an NPC already in this dungeon that doesn't yet have this characteristic
        $npc = Npc::query()
            ->join('enemies', 'enemies.npc_id', '=', 'npcs.id')
            ->where('enemies.mapping_version_id', $mappingVersion->id)
            ->whereNotIn('npcs.id', static function ($sub) use ($spell) {
                $sub->select('npc_id')
                    ->from('npc_characteristics')
                    ->where('characteristic_id', $spell->characteristic_id);
            })
            ->select('npcs.*')
            ->first();
        $this->assertNotNull($npc);

        // Set the user's context dungeon to the one with enemies
        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $dungeon->id;
        $user->save();
        $user = $user->fresh();

        try {
            NpcCharacteristic::create([
                'npc_id'            => $npc->id,
                'characteristic_id' => $spell->characteristic_id,
            ]);

            // Act
            $response = $this->actingAs($user)->get(route('compendium.class.show', $characterClass));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__($npc->name));
        } finally {
            NpcCharacteristic::where('npc_id', $npc->id)
                ->where('characteristic_id', $spell->characteristic_id)
                ->delete();
            $user->dungeon_id = $originalDungeonId;
            $user->save();
        }
    }
}
