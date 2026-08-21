<?php

namespace Tests\Feature\Database\Migrations;

use App\Console\Commands\Database\Migrate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The runtime connections (`mysql`, `combatlog`) are least-privilege - `combatlog` lacks DROP in
 * staging/production (#3963) - so migrations may only ever run on their elevated `*_migrate` counterparts.
 *
 * That is decided by the `--database` the caller passes, which means a migration must not name a connection
 * of its own: `protected $connection`, `Schema::connection()` and `DB::connection()` all override the
 * caller and would silently put the work back on the connection that cannot do it (#4242). Nothing about
 * that failure is visible until a deploy hits the one statement the runtime user lacks the grant for, so it
 * is asserted here across every migration rather than left to review.
 */
#[Group('CombatLog')]
#[Group('MigrationConnection')]
final class MigrationConnectionTest extends PublicTestCase
{
    /**
     * The `*_legacy` directories are squashed history - nothing runs them, and they are not held to this.
     *
     * @return array<string, array{migrationPath: string}>
     */
    public static function migrations_givenEachOfThem_neverNameANonMigrateConnection_DataProvider(): array
    {
        return [
            'main'       => ['migrationPath' => 'database/migrations'],
            'combat log' => ['migrationPath' => Migrate::COMBAT_LOG_MIGRATE_OPTIONS['--path']],
        ];
    }

    #[Test]
    #[DataProvider('migrations_givenEachOfThem_neverNameANonMigrateConnection_DataProvider')]
    public function migrations_givenEachOfThem_neverNameANonMigrateConnection(string $migrationPath): void
    {
        // Arrange
        $allowedConnections = [
            Migrate::MIGRATE_OPTIONS['--database'],
            Migrate::COMBAT_LOG_MIGRATE_OPTIONS['--database'],
        ];

        $migrations = glob(sprintf('%s/*.php', base_path($migrationPath)));

        // Act & Assert
        $this->assertNotEmpty($migrations, sprintf('No migrations found in %s', $migrationPath));

        foreach ($migrations as $migration) {
            foreach ($this->getNamedConnections($migration) as $namedConnection) {
                $this->assertContains(
                    $namedConnection,
                    $allowedConnections,
                    sprintf(
                        '%s names the "%s" connection, which is not one a migration may run on (%s). Drop the ' .
                        'connection so it inherits the --database it was invoked with, or name the elevated one.',
                        basename($migration),
                        $namedConnection,
                        implode(', ', $allowedConnections),
                    ),
                );
            }
        }
    }

    /**
     * Both migrate connections have to exist for the options above to resolve to anything at all - a rename in
     * config/database.php that misses the callers would otherwise only surface as a runtime failure.
     */
    #[Test]
    public function migrateConnections_givenTheDatabaseConfig_areAllConfigured(): void
    {
        // Arrange
        // Act
        $connections = config('database.connections');

        // Assert
        $this->assertArrayHasKey(Migrate::MIGRATE_OPTIONS['--database'], $connections);
        $this->assertArrayHasKey(Migrate::COMBAT_LOG_MIGRATE_OPTIONS['--database'], $connections);
    }

    /**
     * Laravel resolves the squashed schema dump by connection name, so on `combatlog_migrate` it looks for a
     * `combatlog_migrate-schema.dump` that has never existed - and its loader guards on is_file() and returns
     * silently, so a missing dump is skipped without an error and the incremental migrations run against an
     * empty database. The explicit `--schema-path` is the only thing standing between that and a `migrate`
     * that looks fine right up until it dies on a table the dump was supposed to have created, so the file it
     * names has to be real.
     */
    #[Test]
    public function combatLogMigrateOptions_givenTheSchemaPath_pointAtAFileThatExists(): void
    {
        // Arrange
        // Act
        $schemaPath = Migrate::COMBAT_LOG_MIGRATE_OPTIONS['--schema-path'];

        // Assert
        $this->assertNotEmpty($schemaPath, 'The combat log migrations must name their schema dump explicitly.');
        $this->assertFileExists(base_path($schemaPath));
    }

    /**
     * Read from the source rather than by requiring the migration: `require` returns `true` rather than the
     * migration the second time a file is loaded in a process, and it would not see a `DB::connection()` buried
     * in a closure at all.
     *
     * @return array<int, string>
     */
    private function getNamedConnections(string $migrationFilePath): array
    {
        $matches = [];

        preg_match_all(
            '/(?:protected\s+\$connection\s*=|(?:Schema|DB)::connection\()\s*[\'"]([^\'"]+)[\'"]/',
            file_get_contents($migrationFilePath),
            $matches,
        );

        return array_unique($matches[1]);
    }
}
