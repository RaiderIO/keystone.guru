<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Dungeon;
use App\Models\Speedrun\DungeonSpeedrunDifficulty;
use Database\Seeders\DatabaseSeeder;

class DungeonSpeedrunDifficultiesRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Dungeon::class;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'dungeon_speedrun_difficulties';
    }

    /**
     * @param  array<string, mixed> $modelData
     * @param  array<string, mixed> $value
     * @return array<string, mixed>
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $dungeonSpeedrunDifficulty) {
            $dungeonSpeedrunDifficulty['dungeon_id'] = $modelData['id'];

            DungeonSpeedrunDifficulty::from(DatabaseSeeder::getTempTableName(DungeonSpeedrunDifficulty::class))
                ->insert($dungeonSpeedrunDifficulty);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
