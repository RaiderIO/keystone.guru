<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\CombatLog\CombatLogParsingCriterion;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the cleanup of the mythic_level_min = 0 / mythic_level_max = 0 rows that the band
 * migration (2026_08_12_120000) backfilled onto pre-existing rows - see the migration's own
 * docblock and #4038. Those rows surfaced on the admin criteria page as a phantom "Key level 0-0"
 * band.
 *
 * The migration sweeps every 0/0 row, not just the fixture, and its down() is a deliberate
 * no-op, so every call runs inside a transaction that is always rolled back; that disposes of the
 * fixture row too.
 */
#[Group('CombatLog')]
final class DeleteOrphanZeroBandCombatLogParsingCriteriaTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_08_15_120000_delete_orphan_zero_band_combat_log_parsing_criteria.php';

    #[Test]
    public function up_givenOrphanZeroBandRow_deletesItButKeepsRealBands(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $orphan = CombatLogParsingCriterion::factory()->forDungeon(999901)->forBand(0, 0)->create();
            $real   = CombatLogParsingCriterion::factory()->forDungeon(999902)->forBand(2, 6)->create();
            $top    = CombatLogParsingCriterion::factory()->forDungeon(999903)->forBand(30, null)->create();

            // Act
            $migration = require database_path(self::MIGRATION);
            $migration->up();

            // Assert
            $this->assertNull(
                CombatLogParsingCriterion::query()->find($orphan->id),
                'The orphan 0/0 band row should have been deleted.',
            );
            $this->assertNotNull(
                CombatLogParsingCriterion::query()->find($real->id),
                'A real spread band row must not be touched.',
            );
            $this->assertNotNull(
                CombatLogParsingCriterion::query()->find($top->id),
                'The open-ended top band row must not be touched.',
            );
        } finally {
            DB::rollBack();
        }
    }
}
