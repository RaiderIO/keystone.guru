<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\Spell\Spell;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * @param class-string<Model> $modelClass
     * @param list<string>        $uniqueColumns
     */
    #[Test]
    #[DataProvider('observationTableProvider')]
    public function observationTable_givenMigratedSchema_keepsTheUniqueIndexOnlyOnTheObservationKey(
        string $modelClass,
        string $uniqueIndexName,
        string $droppedIndexName,
        array  $uniqueColumns,
    ): void {
        // Arrange
        /** @var Model $model */
        $model          = new $modelClass();
        $connectionName = $model->getConnectionName();
        $table          = $model->getTable();

        // Act
        $uniqueIndex  = $this->findIndex($connectionName, $table, $uniqueIndexName);
        $droppedIndex = $this->findIndex($connectionName, $table, $droppedIndexName);

        // Assert
        $this->assertNotNull($uniqueIndex);
        $this->assertTrue($uniqueIndex['unique']);
        $this->assertSame($uniqueColumns, $uniqueIndex['columns']);
        $this->assertNull($droppedIndex);
    }

    /**
     * @return array<string, array{class-string<Model>, string, string, list<string>}>
     */
    public static function observationTableProvider(): array
    {
        return [
            'spell property observations' => [
                CombatLogSpellPropertyObservation::class,
                'clspo_spell_property_date_unique',
                'clspo_spell_property_date_index',
                ['spell_id', 'property', 'observed_on'],
            ],
            'npc characteristic observations' => [
                CombatLogNpcCharacteristicObservation::class,
                'clnco_npc_char_date_unique',
                'clnco_npc_char_date_index',
                ['npc_id', 'characteristic_id', 'observed_on'],
            ],
        ];
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
