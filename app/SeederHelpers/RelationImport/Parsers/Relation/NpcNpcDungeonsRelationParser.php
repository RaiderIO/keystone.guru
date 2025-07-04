<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use Database\Seeders\DatabaseSeeder;

class NpcNpcDungeonsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Npc::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'npc_dungeons';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        NpcDungeon::from(DatabaseSeeder::getTempTableName(NpcDungeon::class))->insert($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
