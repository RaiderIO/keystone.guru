<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\Spell\Spell;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Pins the indexes the combat log ingest path relies on, against the migrated test schema.
 */
#[Group('CombatLog')]
#[Group('CombatLogIngestIndexes')]
final class CombatLogIngestIndexesTest extends PublicTestCase
{
    #[Test]
    public function spells_givenMigratedSchema_indexesCharacteristicId(): void
    {
        // Arrange
        $connectionName = new Spell()->getConnectionName();

        // Act
        $index = $this->findIndex($connectionName, 'spells', 'spells_characteristic_id_index');

        // Assert
        $this->assertNotNull($index);
        $this->assertSame(['characteristic_id'], $index['columns']);
        $this->assertFalse($index['unique']);
    }

    /**
     * @return array{name: string, columns: list<string>, unique: bool}|null
     */
    private function findIndex(?string $connectionName, string $table, string $indexName): ?array
    {
        return collect(Schema::connection($connectionName)->getIndexes($table))
            ->first(static fn(array $index) => $index['name'] === $indexName);
    }
}
