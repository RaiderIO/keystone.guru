<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('StoreLatLngAtFullPrecisionMigration')]
final class StoreLatLngAtFullPrecisionTest extends PublicTestCase
{
    private const array TABLES = [
        'dungeon_floor_switch_markers',
        'enemies',
        'floor_unions',
    ];

    /**
     * The real tables hold the persistent seeded mapping data, which narrowing them would round for good. A
     * temporary table shadows the real one of the same name on this connection only, so the migration
     * alters the temporary table instead.
     */
    #[Test]
    #[DataProvider('up_givenTwoDecimalLatLngColumns_widensThemToFullPrecision_dataProvider')]
    public function up_givenTwoDecimalLatLngColumns_widensThemToFullPrecision(string $tableName): void
    {
        // Arrange
        $migration = $this->requireMigration();

        try {
            $this->createTwoDecimalTemporaryTables();

            // Act
            $migration->up();

            // Assert
            $this->assertSame(['lat' => 'double', 'lng' => 'double'], $this->getLatLngColumnTypes($tableName));
        } finally {
            $this->dropTemporaryTables();
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function up_givenTwoDecimalLatLngColumns_widensThemToFullPrecision_dataProvider(): array
    {
        return [
            'dungeon_floor_switch_markers' => ['dungeon_floor_switch_markers'],
            'enemies'                      => ['enemies'],
            'floor_unions'                 => ['floor_unions'],
        ];
    }

    #[Test]
    public function up_givenTheMigratedSchema_leavesNoLatLngColumnWithAFixedScale(): void
    {
        // Arrange
        $connection = DB::connection();

        // Act
        $fixedScaleColumns = collect($connection->select(
            'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME IN (\'lat\', \'lng\')',
            [$connection->getDatabaseName()],
        ))->filter(static fn(object $column): bool => str_contains($column->COLUMN_TYPE, ','))
            ->map(static fn(object $column): string => sprintf('%s.%s %s', $column->TABLE_NAME, $column->COLUMN_NAME, $column->COLUMN_TYPE))
            ->values()
            ->all();

        // Assert
        $this->assertSame([], $fixedScaleColumns);
    }

    private function createTwoDecimalTemporaryTables(): void
    {
        foreach (self::TABLES as $tableName) {
            DB::statement(sprintf('CREATE TEMPORARY TABLE `%s` (`lat` double(8,2) NOT NULL, `lng` double(8,2) NOT NULL)', $tableName));
        }
    }

    private function dropTemporaryTables(): void
    {
        foreach (self::TABLES as $tableName) {
            DB::statement(sprintf('DROP TEMPORARY TABLE IF EXISTS `%s`', $tableName));
        }
    }

    /**
     * SHOW COLUMNS sees a temporary table; information_schema, which Schema::getColumns() reads, does not.
     *
     * @return array<string, string>
     */
    private function getLatLngColumnTypes(string $tableName): array
    {
        return collect(DB::select(sprintf('SHOW COLUMNS FROM `%s`', $tableName)))
            ->whereIn('Field', ['lat', 'lng'])
            ->sortBy('Field')
            ->mapWithKeys(static fn(object $column): array => [$column->Field => $column->Type])
            ->all();
    }

    private function requireMigration(): mixed
    {
        return require base_path('database/migrations/2026_10_08_000000_store_lat_lng_at_full_precision.php');
    }
}
