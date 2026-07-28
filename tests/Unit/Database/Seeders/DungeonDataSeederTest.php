<?php

namespace Tests\Unit\Database\Seeders;

use App\Models\Traits\SeederModel;
use App\SeederHelpers\RelationImport\Mapping\RelationMapping;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DungeonDataSeeder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class DungeonDataSeederTest extends TestCase
{
    #[Test]
    public function getAffectedModelClasses_givenEveryRelationMapping_coversExactlyTheSeederModels(): void
    {
        // Arrange - DatabaseSeeder::getTempTableName() appends `_temp` for any model using the SeederModel
        // trait, and flushModels() inserts into that name. The temp table itself is only ever created for a
        // model listed in getAffectedModelClasses(), so a SeederModel that is registered as a RelationMapping
        // but missing from that list makes the seeder insert into a table that does not exist - the moment its
        // JSON file holds a single row. That is what happened to EnemyForcesCheckpoint (#3702).
        //
        // The reverse must hold too: a model that does NOT use SeederModel writes straight to its live table,
        // so listing it would build a temp table that is renamed over the live data while it is still empty.
        $relationMappingsProperty = new ReflectionProperty(DungeonDataSeeder::class, 'relationMapping');

        /** @var array<int, RelationMapping> $relationMappings */
        $relationMappings     = $relationMappingsProperty->getValue(new DungeonDataSeeder());
        $affectedModelClasses = DungeonDataSeeder::getAffectedModelClasses();

        $this->assertNotEmpty($relationMappings, 'DungeonDataSeeder should register relation mappings.');

        foreach ($relationMappings as $relationMapping) {
            $class = $relationMapping->getClass();

            // Act
            $usesTempTable = str_ends_with(DatabaseSeeder::getTempTableName($class), DatabaseSeeder::TEMP_TABLE_SUFFIX);

            // Assert
            $this->assertSame(
                $usesTempTable,
                in_array($class, $affectedModelClasses, true),
                sprintf(
                    '%s %s the %s trait, so it %s be listed in DungeonDataSeeder::getAffectedModelClasses().',
                    $class,
                    $usesTempTable ? 'uses' : 'does not use',
                    class_basename(SeederModel::class),
                    $usesTempTable ? 'must' : 'must not',
                ),
            );
        }
    }
}
