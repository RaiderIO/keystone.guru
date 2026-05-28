<?php

namespace Tests\Feature\App\Console\Commands\Spell;

use App\Console\Commands\Spell\AssignMissingSpellDungeons;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('AssignMissingSpellDungeons')]
final class AssignMissingSpellDungeonsTest extends PublicTestCase
{
    private const int NPC_ID     = 9995097;
    private const int SPELL_ID   = 9995096;
    private const int DUNGEON_ID = 1;

    protected function tearDown(): void
    {
        try {
            NpcSpell::where('npc_id', self::NPC_ID)->delete();
            NpcDungeon::where('npc_id', self::NPC_ID)->delete();
            SpellDungeon::where('spell_id', self::SPELL_ID)->delete();
            Npc::where('id', self::NPC_ID)->delete();
            Spell::where('id', self::SPELL_ID)->delete();
        } finally {
            parent::tearDown();
        }
    }

    private function createTestNpc(): Npc
    {
        return Npc::create([
            'id'                => self::NPC_ID,
            'classification_id' => 1,
            'npc_type_id'       => 1,
            'npc_class_id'      => 1,
            'display_id'        => null,
            'name'              => 'Test NPC',
            'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
            'dangerous'         => 0,
            'truesight'         => 0,
        ]);
    }

    private function createTestSpell(): Spell
    {
        return Spell::create([
            'id'              => self::SPELL_ID,
            'game_version_id' => 1,
            'dispel_type'     => '',
            'mechanic'        => '',
            'icon_name'       => '',
            'name'            => 'Test Spell',
            'schools_mask'    => 1,
            'miss_types_mask' => 0,
            'aura'            => false,
            'debuff'          => false,
            'cast_time'       => 0,
            'duration'        => 0,
            'selectable'      => false,
            'hidden_on_map'   => false,
            'fetched_data_at' => Carbon::now(),
        ]);
    }

    #[Test]
    public function handle_givenNpcWithDungeonAndSpellMissingDungeon_assignsDungeonToSpell(): void
    {
        // Arrange
        $this->createTestNpc();
        $this->createTestSpell();
        NpcDungeon::create(['npc_id' => self::NPC_ID, 'dungeon_id' => self::DUNGEON_ID]);
        NpcSpell::create(['npc_id' => self::NPC_ID, 'spell_id' => self::SPELL_ID]);

        // Act
        $this->artisan(AssignMissingSpellDungeons::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('spell_dungeons', [
            'spell_id'   => self::SPELL_ID,
            'dungeon_id' => self::DUNGEON_ID,
        ]);
    }

    #[Test]
    public function handle_givenSpellAlreadyAssignedToDungeon_doesNotCreateDuplicate(): void
    {
        // Arrange
        $this->createTestNpc();
        $this->createTestSpell();
        NpcDungeon::create(['npc_id' => self::NPC_ID, 'dungeon_id' => self::DUNGEON_ID]);
        NpcSpell::create(['npc_id' => self::NPC_ID, 'spell_id' => self::SPELL_ID]);
        SpellDungeon::create(['spell_id' => self::SPELL_ID, 'dungeon_id' => self::DUNGEON_ID]);

        // Act
        $this->artisan(AssignMissingSpellDungeons::class)->assertSuccessful();

        // Assert — exactly one SpellDungeon record, not two
        $this->assertSame(
            1,
            SpellDungeon::where('spell_id', self::SPELL_ID)->where('dungeon_id', self::DUNGEON_ID)->count(),
        );
    }

    #[Test]
    public function handle_givenNpcWithNoDungeons_doesNotAssignSpellDungeon(): void
    {
        // Arrange
        $this->createTestNpc();
        $this->createTestSpell();
        NpcSpell::create(['npc_id' => self::NPC_ID, 'spell_id' => self::SPELL_ID]);
        // Deliberately no NpcDungeon

        // Act
        $this->artisan(AssignMissingSpellDungeons::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('spell_dungeons', ['spell_id' => self::SPELL_ID]);
    }

    #[Test]
    public function handle_givenNpcWithMultipleDungeons_assignsAllMissingDungeons(): void
    {
        // Arrange
        $secondDungeonId = 2;
        $this->createTestNpc();
        $this->createTestSpell();
        NpcDungeon::create(['npc_id' => self::NPC_ID, 'dungeon_id' => self::DUNGEON_ID]);
        NpcDungeon::create(['npc_id' => self::NPC_ID, 'dungeon_id' => $secondDungeonId]);
        NpcSpell::create(['npc_id' => self::NPC_ID, 'spell_id' => self::SPELL_ID]);

        // Act
        $this->artisan(AssignMissingSpellDungeons::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('spell_dungeons', ['spell_id' => self::SPELL_ID, 'dungeon_id' => self::DUNGEON_ID]);
        $this->assertDatabaseHas('spell_dungeons', ['spell_id' => self::SPELL_ID, 'dungeon_id' => $secondDungeonId]);
    }

    #[Test]
    public function handle_givenNpcWithMultipleDungeons_oneAlreadyAssigned_onlyAssignsMissingDungeon(): void
    {
        // Arrange
        $secondDungeonId = 2;
        $this->createTestNpc();
        $this->createTestSpell();
        NpcDungeon::create(['npc_id' => self::NPC_ID, 'dungeon_id' => self::DUNGEON_ID]);
        NpcDungeon::create(['npc_id' => self::NPC_ID, 'dungeon_id' => $secondDungeonId]);
        NpcSpell::create(['npc_id' => self::NPC_ID, 'spell_id' => self::SPELL_ID]);
        SpellDungeon::create(['spell_id' => self::SPELL_ID, 'dungeon_id' => self::DUNGEON_ID]);

        // Act
        $this->artisan(AssignMissingSpellDungeons::class)->assertSuccessful();

        // Assert — first dungeon still has exactly one record, second dungeon was added
        $this->assertSame(
            1,
            SpellDungeon::where('spell_id', self::SPELL_ID)->where('dungeon_id', self::DUNGEON_ID)->count(),
        );
        $this->assertDatabaseHas('spell_dungeons', ['spell_id' => self::SPELL_ID, 'dungeon_id' => $secondDungeonId]);
    }
}
