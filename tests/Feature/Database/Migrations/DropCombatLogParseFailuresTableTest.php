<?php

namespace Tests\Feature\Database\Migrations;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The runtime `combatlog` connection is least-privilege and lacks DROP - only the elevated
 * `combatlog_migrate` connection may run this migration, see #3963.
 */
#[Group('CombatLog')]
final class DropCombatLogParseFailuresTableTest extends PublicTestCase
{
    private const MIGRATION = 'migrations_combatlog/2026_08_06_000001_drop_combat_log_parse_failures_table.php';

    #[Test]
    public function migration_usesTheElevatedCombatlogMigrateConnection(): void
    {
        // Arrange
        // Act
        $migration = require database_path(self::MIGRATION);

        // Assert
        $this->assertSame('combatlog_migrate', $migration->getConnection());
    }
}
