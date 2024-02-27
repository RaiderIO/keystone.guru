<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Npc;
use App\Models\Npc\NpcEnemyForces;
use Database\Seeders\DatabaseSeeder;

class NpcNpcEnemyForcesRelationParser implements RelationParserInterface
{
    public function canParseRootModel(string $modelClassName): bool
    {
        return false;
    }

    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Npc::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'npc_enemy_forces';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        NpcEnemyForces::from(DatabaseSeeder::getTempTableName(NpcEnemyForces::class))->insert($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
