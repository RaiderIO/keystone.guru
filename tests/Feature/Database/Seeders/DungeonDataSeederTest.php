<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use Illuminate\Testing\Constraints\HasInDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the decoupling of combat-log-derived behavior from the git seeder pipeline (#3354):
 * a DungeonDataSeeder run must not wipe the combat-log-derived data that no longer lives in the
 * seeder JSON files.
 *
 * A DungeonDataSeeder run costs ~60s on CI, so every invariant is arranged up front and asserted
 * after a single run. Each assertion message names the invariant it pins.
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

    private const string INVARIANT_SPELL_COLUMNS = 'Live spell behavior columns are preserved across a re-seed (#3354)';
    private const string INVARIANT_PIVOT_ROWS    = 'Combat-log-derived pivot rows survive a re-seed (#3354)';
    private const string INVARIANT_DEMO_ROUTES   = 'Demo dungeon routes survive a re-seed with demo = true (#3376)';

    #[Test]
    public function run_givenOverriddenSpellColumnsAndSentinelPivotRows_restoresSeededStateInOneRun(): void
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

            // Sentinel pivot rows that appear in no seeder JSON; they only survive if the seeder does
            // not rebuild these tables.
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

            // Demo routes need no arranging: rollback() deletes every demo = true route and rebuilds
            // them from the JSON files, which do contain demo routes.

            // Act
            $this->artisan('db:seedone', ['className' => 'DungeonDataSeeder'])->assertSuccessful();

            // Assert - preserveColumns() must have copied the live values back onto the rebuilt row.
            $this->assertDatabaseHasWithMessage('spells', [
                'id'              => $spell->id,
                'aura'            => 1,
                'debuff'          => 1,
                'miss_types_mask' => 15,
            ], self::INVARIANT_SPELL_COLUMNS);

            // Assert - the sentinel pivot rows are still there.
            $this->assertDatabaseHasWithMessage('npc_spells', [
                'npc_id'   => self::SENTINEL_NPC_ID,
                'spell_id' => self::SENTINEL_SPELL_ID,
            ], self::INVARIANT_PIVOT_ROWS);
            $this->assertDatabaseHasWithMessage('npc_characteristics', [
                'npc_id'            => self::SENTINEL_NPC_ID,
                'characteristic_id' => self::SENTINEL_CHARACTERISTIC_ID,
            ], self::INVARIANT_PIVOT_ROWS);
            $this->assertDatabaseHasWithMessage('spell_dungeons', [
                'spell_id'   => self::SENTINEL_SPELL_ID,
                'dungeon_id' => self::SENTINEL_DUNGEON_ID,
            ], self::INVARIANT_PIVOT_ROWS);

            // Assert - the rebuild uses forceCreate() so the demo column (deliberately kept out of
            // $fillable) is imported verbatim. With a plain create() mass assignment would drop demo and
            // every rebuilt route would land as demo = false, dropping this count to zero.
            $this->assertGreaterThan(
                0,
                DungeonRoute::query()->where('demo', true)->count(),
                self::INVARIANT_DEMO_ROUTES,
            );
        } finally {
            SpellDungeon::query()->where('spell_id', self::SENTINEL_SPELL_ID)->delete();
            NpcCharacteristic::query()->where('npc_id', self::SENTINEL_NPC_ID)->delete();
            NpcSpell::query()->where('npc_id', self::SENTINEL_NPC_ID)->delete();
            Spell::query()->where('id', $spell->id)->update($original);
        }
    }

    /**
     * assertDatabaseHas() with a failure message; Laravel's own variant accepts none.
     *
     * @param array<string, mixed> $data
     */
    private function assertDatabaseHasWithMessage(string $table, array $data, string $message): void
    {
        $this->assertThat($table, new HasInDatabase($this->getConnection(null, $table), $data), $message);
    }
}
