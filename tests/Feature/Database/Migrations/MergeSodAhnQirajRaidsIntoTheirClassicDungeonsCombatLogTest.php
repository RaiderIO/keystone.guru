<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The combat log half of the Season of Discovery Ahn'Qiraj merge. Runs on the combatlog connection, the
 * way a deploy runs it, and deletes the rows it creates.
 */
#[Group('CombatLog')]
#[Group('MergeSodAhnQiraj')]
final class MergeSodAhnQirajRaidsIntoTheirClassicDungeonsCombatLogTest extends PublicTestCase
{
    private const string MIGRATION = 'migrations_combatlog/2026_10_02_200000_merge_sod_ahnqiraj_raids_into_their_classic_dungeons.php';

    private const int SOD_TEMPLE_DUNGEON_ID     = 126;
    private const int CLASSIC_TEMPLE_DUNGEON_ID = 124;
    private const int SOD_HIVE_FLOOR_ID         = 331;
    private const int CLASSIC_HIVE_FLOOR_ID     = 327;

    #[Test]
    public function up_givenChallengeModeRunOnSodDungeon_movesItToClassicDungeon(): void
    {
        // Arrange
        $challengeModeRun = ChallengeModeRun::factory()->create(['dungeon_id' => self::SOD_TEMPLE_DUNGEON_ID]);

        try {
            // Act
            $this->runMigration();

            // Assert
            $this->assertSame(self::CLASSIC_TEMPLE_DUNGEON_ID, $challengeModeRun->fresh()->dungeon_id);
        } finally {
            $challengeModeRun->delete();
        }
    }

    #[Test]
    public function up_givenEnemyFailureOnSodFloor_movesItToClassicDungeonAndFloor(): void
    {
        // Arrange
        $enemyFailure = CombatLogRouteEnemyFailure::factory()->create([
            'dungeon_id' => self::SOD_TEMPLE_DUNGEON_ID,
            'floor_id'   => self::SOD_HIVE_FLOOR_ID,
        ]);

        try {
            // Act
            $this->runMigration();

            // Assert
            $enemyFailure->refresh();
            $this->assertSame(self::CLASSIC_TEMPLE_DUNGEON_ID, $enemyFailure->dungeon_id);
            $this->assertSame(self::CLASSIC_HIVE_FLOOR_ID, $enemyFailure->floor_id);
        } finally {
            $enemyFailure->delete();
        }
    }

    #[Test]
    public function up_givenEnemyResolutionOnSodFloor_movesItToClassicDungeonAndFloor(): void
    {
        // Arrange
        $enemyResolution = CombatLogRouteEnemyResolution::factory()->create([
            'dungeon_id' => self::SOD_TEMPLE_DUNGEON_ID,
            'floor_id'   => self::SOD_HIVE_FLOOR_ID,
        ]);

        try {
            // Act
            $this->runMigration();

            // Assert
            $enemyResolution->refresh();
            $this->assertSame(self::CLASSIC_TEMPLE_DUNGEON_ID, $enemyResolution->dungeon_id);
            $this->assertSame(self::CLASSIC_HIVE_FLOOR_ID, $enemyResolution->floor_id);
        } finally {
            $enemyResolution->delete();
        }
    }

    private function runMigration(): void
    {
        $migration         = require database_path(self::MIGRATION);
        $defaultConnection = DB::getDefaultConnection();

        DB::setDefaultConnection('combatlog');

        try {
            $migration->up();
        } finally {
            DB::setDefaultConnection($defaultConnection);
        }
    }
}
