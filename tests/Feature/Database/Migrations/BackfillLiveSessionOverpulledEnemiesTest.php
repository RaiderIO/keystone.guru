<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The migration copies every legacy row and its down() is a no-op, so every call runs inside a transaction that is
 * always rolled back; that disposes of the fixture rows too.
 */
#[Group('LiveSession')]
final class BackfillLiveSessionOverpulledEnemiesTest extends PublicTestCase
{
    private const string MIGRATION = 'migrations/2026_10_07_000000_backfill_live_session_overpulled_enemies_table.php';

    private const int LIVE_SESSION_ID = 999999901;

    #[Test]
    public function up_givenLegacyRows_copiesTheMissingOnesOnce(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            DB::table('overpulled_enemies')->insert([
                ['live_session_id' => self::LIVE_SESSION_ID, 'kill_zone_id' => 11, 'npc_id' => 500, 'mdt_id' => 1],
                ['live_session_id' => self::LIVE_SESSION_ID, 'kill_zone_id' => 12, 'npc_id' => 500, 'mdt_id' => 2],
            ]);
            DB::table('live_session_overpulled_enemies')->insert(
                ['live_session_id' => self::LIVE_SESSION_ID, 'kill_zone_id' => 13, 'npc_id' => 500, 'mdt_id' => 2],
            );

            // Act
            $migration = require database_path(self::MIGRATION);
            $migration->up();
            $migration->up();

            // Assert
            $rows = DB::table('live_session_overpulled_enemies')
                ->where('live_session_id', self::LIVE_SESSION_ID)
                ->orderBy('mdt_id')
                ->get(['kill_zone_id', 'mdt_id'])
                ->map(static fn(object $row) => [(int)$row->kill_zone_id, (int)$row->mdt_id])
                ->all();
            $this->assertSame([[11, 1], [13, 2]], $rows);
        } finally {
            DB::rollBack();
        }
    }
}
