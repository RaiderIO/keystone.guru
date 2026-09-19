<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\CombatLog\CombatLogParsingCriterion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Rolling back restores a unique index without mythic_level_max, so a top band and a spread band row on
 * the same floor have to be collapsed into one first.
 */
#[Group('CombatLog')]
#[Group('CombatLogParsingCriteriaMigration')]
final class AddMythicLevelMaxToCombatLogParsingCriteriaUniqueIndexTest extends PublicTestCase
{
    private const int DUNGEON_ID       = 999905;
    private const int OTHER_DUNGEON_ID = 999906;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteTestRows();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteTestRows();

        parent::tearDown();
    }

    #[Test]
    public function down_givenAnOlderTopBandRowAndANewerSpreadBandRowOnTheSameFloor_keepsTheSpreadBandRow(): void
    {
        // Arrange
        $migration  = $this->requireMigration();
        $topBand    = CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(17, null)->withCount(5)->create(['threshold' => 0]);
        $spreadBand = CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(17, 18)->withCount(2)->create(['threshold' => 300]);

        try {
            // Act
            $migration->down();

            // Assert
            $this->assertNull(CombatLogParsingCriterion::query()->find($topBand->id));
            $this->assertSame(300, CombatLogParsingCriterion::query()->findOrFail($spreadBand->id)->threshold);
        } finally {
            $migration->up();
        }
    }

    #[Test]
    public function down_givenATopBandRowWithoutASpreadBandRowOnItsFloor_keepsTheTopBandRow(): void
    {
        // Arrange
        $migration  = $this->requireMigration();
        $topBand    = CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(17, null)->create(['threshold' => 0]);
        $spreadBand = CombatLogParsingCriterion::factory()->forDungeon(self::OTHER_DUNGEON_ID)->forBand(17, 18)->create();

        try {
            // Act
            $migration->down();

            // Assert
            $this->assertNotNull(CombatLogParsingCriterion::query()->find($topBand->id));
            $this->assertNotNull(CombatLogParsingCriterion::query()->find($spreadBand->id));
        } finally {
            $migration->up();
        }
    }

    #[Test]
    public function up_givenATopBandRowAndASpreadBandRowOnTheSameFloor_allowsBoth(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(17, null)->create(['threshold' => 0]);

        // Act
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(17, 18)->create();

        // Assert
        $this->assertSame(2, CombatLogParsingCriterion::query()->where('model_id', self::DUNGEON_ID)->count());
    }

    private function requireMigration(): mixed
    {
        return require base_path('database/migrations/2026_09_19_120000_add_mythic_level_max_to_combat_log_parsing_criteria_unique_index.php');
    }

    private function deleteTestRows(): void
    {
        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::OTHER_DUNGEON_ID])
            ->delete();
    }
}
