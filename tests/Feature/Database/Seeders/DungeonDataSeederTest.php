<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the decoupling of combat-log-derived behavior from the git seeder pipeline (#3354):
 * a DungeonDataSeeder run must not wipe the combat-log-derived data that no longer lives in the
 * seeder JSON files.
 */
#[Group('DungeonDataSeeder')]
#[SlowTest]
final class DungeonDataSeederTest extends PublicTestCase
{
    /**
     * Sentinel IDs that do not exist in any seeder JSON file, so surviving a re-seed proves the
     * pivot tables are left untouched (not rebuilt from JSON).
     */
    private const int SENTINEL_NPC_ID            = 9994001;
    private const int SENTINEL_SPELL_ID          = 9994002;
    private const int SENTINEL_CHARACTERISTIC_ID = 9994003;
    private const int SENTINEL_DUNGEON_ID        = 9994004;

    #[Test]
    public function run_givenLiveSpellBehaviorColumns_preservesThemAcrossReseed(): void
    {
        // Arrange - pick an existing catalog spell (guaranteed to be in spells.json so it survives the
        // rebuild) and give it non-default combat-log-derived behavior on the live table.
        /** @var Spell $spell */
        $spell    = Spell::query()->firstOrFail();
        $original = [
            'aura'            => $spell->aura,
            'debuff'          => $spell->debuff,
            'miss_types_mask' => $spell->miss_types_mask,
        ];

        try {
            Spell::query()->where('id', $spell->id)->update([
                'aura'            => true,
                'debuff'          => true,
                'miss_types_mask' => 15,
            ]);

            // Act
            $this->artisan('db:seedone', ['className' => 'DungeonDataSeeder'])->assertSuccessful();

            // Assert - preserveColumns() must have copied the live values back onto the rebuilt row.
            $this->assertDatabaseHas('spells', [
                'id'              => $spell->id,
                'aura'            => 1,
                'debuff'          => 1,
                'miss_types_mask' => 15,
            ]);
        } finally {
            Spell::query()->where('id', $spell->id)->update($original);
        }
    }

    #[Test]
    public function run_givenCombatLogDerivedPivotRows_survivesReseed(): void
    {
        // Arrange - sentinel rows that appear in no seeder JSON; they only survive if the seeder does
        // not rebuild these tables.
        try {
            NpcSpell::insert([
                'npc_id'   => self::SENTINEL_NPC_ID,
                'spell_id' => self::SENTINEL_SPELL_ID,
            ]);
            NpcCharacteristic::insert([
                'npc_id'            => self::SENTINEL_NPC_ID,
                'characteristic_id' => self::SENTINEL_CHARACTERISTIC_ID,
            ]);
            SpellDungeon::insert([
                'spell_id'   => self::SENTINEL_SPELL_ID,
                'dungeon_id' => self::SENTINEL_DUNGEON_ID,
            ]);

            // Act
            $this->artisan('db:seedone', ['className' => 'DungeonDataSeeder'])->assertSuccessful();

            // Assert
            $this->assertDatabaseHas('npc_spells', [
                'npc_id'   => self::SENTINEL_NPC_ID,
                'spell_id' => self::SENTINEL_SPELL_ID,
            ]);
            $this->assertDatabaseHas('npc_characteristics', [
                'npc_id'            => self::SENTINEL_NPC_ID,
                'characteristic_id' => self::SENTINEL_CHARACTERISTIC_ID,
            ]);
            $this->assertDatabaseHas('spell_dungeons', [
                'spell_id'   => self::SENTINEL_SPELL_ID,
                'dungeon_id' => self::SENTINEL_DUNGEON_ID,
            ]);
        } finally {
            NpcSpell::query()->where('npc_id', self::SENTINEL_NPC_ID)->delete();
            NpcCharacteristic::query()->where('npc_id', self::SENTINEL_NPC_ID)->delete();
            SpellDungeon::query()->where('spell_id', self::SENTINEL_SPELL_ID)->delete();
        }
    }

    #[Test]
    public function run_givenDemoDungeonRoutes_preservesDemoFlagAcrossReseed(): void
    {
        // Arrange - rollback() deletes every demo = true route and rebuilds them from the JSON files,
        // which do contain demo routes, so a healthy re-seed must leave demo routes behind.

        // Act
        $this->artisan('db:seedone', ['className' => 'DungeonDataSeeder'])->assertSuccessful();

        // Assert - the rebuild uses forceCreate() so the demo column (deliberately kept out of
        // $fillable) is imported verbatim. With a plain create() mass assignment would drop demo and
        // every rebuilt route would land as demo = false, dropping this count to zero (#3376).
        $this->assertGreaterThan(
            0,
            DungeonRoute::query()->where('demo', true)->count(),
            'Demo dungeon routes must survive a re-seed with demo = true',
        );
    }
}
