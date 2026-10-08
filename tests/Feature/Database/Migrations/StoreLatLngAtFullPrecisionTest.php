<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('StoreLatLngAtFullPrecisionMigration')]
final class StoreLatLngAtFullPrecisionTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('up_givenTwoDecimalLatLngColumns_widensThemToFullPrecision_dataProvider')]
    public function up_givenTwoDecimalLatLngColumns_widensThemToFullPrecision(string $tableName): void
    {
        // Arrange
        $migration = $this->requireMigration();
        DB::statement(sprintf('ALTER TABLE `%s` MODIFY `lat` double(8,2) NOT NULL, MODIFY `lng` double(8,2) NOT NULL', $tableName));

        try {
            // Act
            $migration->up();

            // Assert
            $this->assertSame(['lat' => 'double', 'lng' => 'double'], $this->getLatLngColumnTypes($tableName));
        } finally {
            $migration->up();
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

    /**
     * @return array<string, string>
     */
    private function getLatLngColumnTypes(string $tableName): array
    {
        return collect(Schema::getColumns($tableName))
            ->whereIn('name', ['lat', 'lng'])
            ->sortBy('name')
            ->mapWithKeys(static fn(array $column): array => [$column['name'] => $column['type']])
            ->all();
    }

    private function requireMigration(): mixed
    {
        return require base_path('database/migrations/2026_10_08_000000_store_lat_lng_at_full_precision.php');
    }
}
