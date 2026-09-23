<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * DDL implicitly commits in MySQL, so these tests cannot roll back like a data-only migration
 * test - up() and down() are paired within each test to leave the schema exactly as it started.
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
            // Act
            $migration->up();

            // Assert
            $this->assertFalse(Schema::hasTable('enemy_active_auras'));
        } finally {
            $migration->down();
        }
    }

    #[Test]
    public function down_givenTheTableIsGone_recreatesItWithTheOriginalColumns(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);
        $migration->up();

        try {
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
