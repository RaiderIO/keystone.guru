<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * DDL implicitly commits in MySQL, so these tests cannot roll back like a data-only migration
 * test - every test ends by running up() so the schema is left migrated, with the table absent.
 */
#[Group('Migrations')]
final class DropEnemyActiveAurasTableTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_09_23_000000_drop_enemy_active_auras_table.php';

    #[Test]
    public function up_givenTheTableExists_dropsIt(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);

        try {
            $migration->down();
            $this->assertTrue(Schema::hasTable('enemy_active_auras'));

            // Act
            $migration->up();

            // Assert
            $this->assertFalse(Schema::hasTable('enemy_active_auras'));
        } finally {
            $migration->up();
        }
    }

    #[Test]
    public function down_givenTheTableIsGone_recreatesItWithTheOriginalColumns(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);

        try {
            $migration->up();
            $this->assertFalse(Schema::hasTable('enemy_active_auras'));

            // Act
            $migration->down();

            // Assert
            $this->assertTrue(Schema::hasTable('enemy_active_auras'));
            $this->assertTrue(Schema::hasColumns('enemy_active_auras', ['id', 'enemy_id', 'spell_id']));
        } finally {
            $migration->up();
        }
    }
}
