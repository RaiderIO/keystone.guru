<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * A temporary table shadows the real `simulation_craft_raid_events_options` on this connection only, so the
 * migration alters the temporary table and the migrated test schema is left alone.
 */
#[Group('Migrations')]
#[Group('SimulationCraft')]
final class DropShroudedBountyTypeAndThunderingClearSecondsFromSimulationCraftRaidEventsOptionsTableTest extends PublicTestCase
{
    private const string TABLE = 'simulation_craft_raid_events_options';

    #[Test]
    public function up_givenTheColumnsBeforeTheDrop_dropsBothAndKeepsTheRows(): void
    {
        // Arrange
        $migration = $this->requireMigration();

        try {
            DB::statement(sprintf(
                'CREATE TEMPORARY TABLE `%s` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `key_level` int NOT NULL,
                    `shrouded_bounty_type` varchar(255) NOT NULL DEFAULT \'none\',
                    `affix` varchar(255) NOT NULL,
                    `thundering_clear_seconds` int DEFAULT NULL,
                    `raid_buffs_mask` int NOT NULL DEFAULT 0
                )',
                self::TABLE,
            ));
            DB::table(self::TABLE)->insert([
                'key_level'                => 12,
                'shrouded_bounty_type'     => 'crit',
                'affix'                    => 'fortified',
                'thundering_clear_seconds' => 10,
            ]);

            // Act
            $migration->up();

            // Assert
            $this->assertSame(['id', 'key_level', 'affix', 'raid_buffs_mask'], array_keys($this->getColumns()));
            $this->assertSame([12], DB::table(self::TABLE)->pluck('key_level')->all());
        } finally {
            $this->dropTemporaryTable();
        }
    }

    #[Test]
    public function down_givenTheDroppedColumns_restoresThemWithTheirOriginalAttributes(): void
    {
        // Arrange
        $migration = $this->requireMigration();

        try {
            DB::statement(sprintf(
                'CREATE TEMPORARY TABLE `%s` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `key_level` int NOT NULL,
                    `affix` varchar(255) NOT NULL,
                    `raid_buffs_mask` int NOT NULL DEFAULT 0
                )',
                self::TABLE,
            ));
            DB::table(self::TABLE)->insert(['key_level' => 12, 'affix' => 'fortified']);

            // Act
            $migration->down();

            // Assert
            $columns = $this->getColumns();
            $this->assertSame(
                ['id', 'key_level', 'shrouded_bounty_type', 'affix', 'thundering_clear_seconds', 'raid_buffs_mask'],
                array_keys($columns),
            );
            $this->assertSame(['type' => 'varchar(255)', 'null' => 'NO', 'default' => 'none'], $columns['shrouded_bounty_type']);
            $this->assertSame(['type' => 'int', 'null' => 'YES', 'default' => null], $columns['thundering_clear_seconds']);
            $row = DB::table(self::TABLE)->first();
            $this->assertSame('none', $row->shrouded_bounty_type);
            $this->assertNull($row->thundering_clear_seconds);
        } finally {
            $this->dropTemporaryTable();
        }
    }

    /**
     * SHOW COLUMNS sees a temporary table; information_schema, which Schema::getColumns() reads, does not.
     *
     * @return array<string, array{type: string, null: string, default: string|null}>
     */
    private function getColumns(): array
    {
        $result = [];
        foreach (DB::select(sprintf('SHOW COLUMNS FROM `%s`', self::TABLE)) as $column) {
            $result[$column->Field] = [
                'type'    => $column->Type,
                'null'    => $column->Null,
                'default' => $column->Default,
            ];
        }

        return $result;
    }

    private function dropTemporaryTable(): void
    {
        DB::statement(sprintf('DROP TEMPORARY TABLE IF EXISTS `%s`', self::TABLE));
    }

    private function requireMigration(): mixed
    {
        return require base_path('database/migrations/2026_11_27_000000_drop_shrouded_bounty_type_and_thundering_clear_seconds_from_simulation_craft_raid_events_options_table.php');
    }
}
