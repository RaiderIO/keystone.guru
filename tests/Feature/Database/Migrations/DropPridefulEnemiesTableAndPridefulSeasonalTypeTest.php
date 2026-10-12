<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The migration alters the schema, which MySQL commits implicitly, so a transaction cannot undo it.
 * Each test starts from the migrated schema and always ends back on it.
 */
#[Group('Migrations')]
final class DropPridefulEnemiesTableAndPridefulSeasonalTypeTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_10_12_000000_drop_prideful_enemies_table_and_prideful_seasonal_type.php';

    #[Test]
    public function up_givenThePridefulEnemiesTable_dropsIt(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);

        try {
            $migration->down();
            $this->assertTrue(Schema::hasTable('prideful_enemies'));

            // Act
            $migration->up();

            // Assert
            $this->assertFalse(Schema::hasTable('prideful_enemies'));
        } finally {
            $migration->up();
        }
    }

    #[Test]
    public function up_givenThePridefulSeasonalType_removesOnlyThatValueAndKeepsTheColumnAttributes(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);

        try {
            $migration->down();
            $this->assertStringContainsString("'prideful'", $this->seasonalTypeColumn()->Type);

            // Act
            $migration->up();

            // Assert
            $column = $this->seasonalTypeColumn();
            $this->assertStringNotContainsString("'prideful'", $column->Type);
            foreach (['beguiling', 'awakened', 'inspiring', 'tormented', 'encrypted', 'mdt_placeholder', 'requires_activation', 'shrouded', 'shrouded_zul_gamux', 'no_shrouded'] as $value) {
                $this->assertStringContainsString(sprintf("'%s'", $value), $column->Type);
            }
            $this->assertSame('YES', $column->Null);
            $this->assertNull($column->Default);
        } finally {
            $migration->up();
        }
    }

    #[Test]
    public function up_givenEnemiesHoldingPridefulAndOtherSeasonalTypes_clearsOnlyThePridefulOnes(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);
        $enemyIds  = [];

        try {
            $migration->down();
            $pridefulId  = $this->insertEnemy('prideful');
            $beguilingId = $this->insertEnemy('beguiling');
            $enemyIds    = [$pridefulId, $beguilingId];

            // Act
            $migration->up();

            // Assert
            $this->assertNull(DB::table('enemies')->where('id', $pridefulId)->value('seasonal_type'));
            $this->assertSame('beguiling', DB::table('enemies')->where('id', $beguilingId)->value('seasonal_type'));
        } finally {
            $migration->up();
            DB::table('enemies')->whereIn('id', $enemyIds)->delete();
        }
    }

    #[Test]
    public function down_givenTheMigrationWasApplied_restoresTheTableAndTheEnumValue(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);

        try {
            $this->assertFalse(Schema::hasTable('prideful_enemies'));

            // Act
            $migration->down();

            // Assert
            $this->assertTrue(Schema::hasTable('prideful_enemies'));
            $this->assertSame(
                ['id', 'dungeon_route_id', 'enemy_id', 'floor_id', 'lat', 'lng', 'created_at', 'updated_at'],
                Schema::getColumnListing('prideful_enemies'),
            );
            $this->assertStringContainsString("'prideful'", $this->seasonalTypeColumn()->Type);
        } finally {
            $migration->up();
        }
    }

    private function insertEnemy(string $seasonalType): int
    {
        return DB::table('enemies')->insertGetId([
            'floor_id'      => 0,
            'faction'       => 'any',
            'lat'           => 0,
            'lng'           => 0,
            'seasonal_type' => $seasonalType,
        ]);
    }

    private function seasonalTypeColumn(): object
    {
        return DB::selectOne("SHOW COLUMNS FROM `enemies` LIKE 'seasonal_type'");
    }
}
