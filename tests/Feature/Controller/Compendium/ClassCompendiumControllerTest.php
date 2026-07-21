<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\CharacterClass;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Spell\Spell;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fixtures\DungeonFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class ClassCompendiumControllerTest extends PublicTestCase
{
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
    public function show_givenNpcWithMatchingCharacteristic_displaysNpcName(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();

        // Find a dungeon whose current mapping version has enemies for the Retail game version
        $defaultGameVersion = GameVersion::getDefaultGameVersion();
        $dungeon            = DungeonFixtures::getDungeonWithCurrentMappingVersionWithEnemies($defaultGameVersion->id);
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
