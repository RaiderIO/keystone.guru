<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Only up() is exercised: down() would shift the seeded enemies the migration renumbered back out of place.
 */
#[Group('Enemy')]
#[Group('RenumberLeakedEnemyIdsMigration')]
final class RenumberLeakedEnemyIdsTest extends PublicTestCase
{
    private const int LEAKED_ENEMY_ID = 999999200;

    private const int RENUMBERED_ENEMY_ID = 151374;

    private const int UNRELATED_KILL_ZONE_ID = 0;

    #[Test]
    public function up_givenReferencesToALeakedEnemyId_movesThemToTheRenumberedId(): void
    {
        // Arrange
        $killZoneEnemyId = DB::table('kill_zone_enemies')->insertGetId([
            'kill_zone_id' => self::UNRELATED_KILL_ZONE_ID,
            'enemy_id'     => self::LEAKED_ENEMY_ID,
        ]);
        $userReportId = $this->insertUserReport(Enemy::class, self::LEAKED_ENEMY_ID);

        try {
            // Act
            $this->runMigration();

            // Assert
            $this->assertSame(self::RENUMBERED_ENEMY_ID, (int)DB::table('kill_zone_enemies')->where('id', $killZoneEnemyId)->value('enemy_id'));
            $this->assertSame(self::RENUMBERED_ENEMY_ID, (int)DB::table('user_reports')->where('id', $userReportId)->value('model_id'));
        } finally {
            DB::table('kill_zone_enemies')->where('id', $killZoneEnemyId)->delete();
            DB::table('user_reports')->where('id', $userReportId)->delete();
        }
    }

    #[Test]
    public function up_givenReferencesOutsideTheLeakedRangeOrToAnotherModel_leavesThemAlone(): void
    {
        // Arrange - one id either side of the leaked range, and a leaked id that belongs to a kill zone
        $killZoneEnemyIds = [
            DB::table('kill_zone_enemies')->insertGetId(['kill_zone_id' => self::UNRELATED_KILL_ZONE_ID, 'enemy_id' => 999999105]),
            DB::table('kill_zone_enemies')->insertGetId(['kill_zone_id' => self::UNRELATED_KILL_ZONE_ID, 'enemy_id' => 999999530]),
        ];
        $userReportId = $this->insertUserReport(KillZone::class, self::LEAKED_ENEMY_ID);

        try {
            // Act
            $this->runMigration();

            // Assert
            $this->assertSame(
                [999999105, 999999530],
                DB::table('kill_zone_enemies')->whereIn('id', $killZoneEnemyIds)->orderBy('id')->pluck('enemy_id')->map(static fn($id) => (int)$id)->all(),
            );
            $this->assertSame(self::LEAKED_ENEMY_ID, (int)DB::table('user_reports')->where('id', $userReportId)->value('model_id'));
        } finally {
            DB::table('kill_zone_enemies')->whereIn('id', $killZoneEnemyIds)->delete();
            DB::table('user_reports')->where('id', $userReportId)->delete();
        }
    }

    #[Test]
    public function up_givenAnEnemyWithALeakedId_renumbersItAndLowersTheAutoIncrement(): void
    {
        // Arrange - put a seeded, already renumbered enemy back on its leaked id
        DB::table('enemies')->where('id', self::RENUMBERED_ENEMY_ID)->update(['id' => self::LEAKED_ENEMY_ID]);

        try {
            // Act
            $this->runMigration();

            // Assert
            $this->assertTrue(Enemy::query()->whereKey(self::RENUMBERED_ENEMY_ID)->exists());
            $this->assertFalse(Enemy::query()->whereKey(self::LEAKED_ENEMY_ID)->exists());
            $this->assertLessThan(self::LEAKED_ENEMY_ID, $this->getEnemiesAutoIncrement());
        } finally {
            DB::table('enemies')->where('id', self::LEAKED_ENEMY_ID)->update(['id' => self::RENUMBERED_ENEMY_ID]);
            DB::statement('ALTER TABLE `enemies` AUTO_INCREMENT = 1');
        }
    }

    private function runMigration(): void
    {
        (require base_path('database/migrations/2026_10_27_000000_renumber_leaked_enemy_ids.php'))->up();
    }

    private function insertUserReport(string $modelClass, int $modelId): int
    {
        return DB::table('user_reports')->insertGetId([
            'user_id'     => 1,
            'model_class' => $modelClass,
            'model_id'    => $modelId,
            'category'    => 'enemy_incorrect',
            'message'     => 'RenumberLeakedEnemyIdsTest',
        ]);
    }

    private function getEnemiesAutoIncrement(): int
    {
        DB::statement('ANALYZE TABLE `enemies`');

        return (int)DB::selectOne(
            'SELECT AUTO_INCREMENT AS auto_increment FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            ['enemies'],
        )->auto_increment;
    }
}
