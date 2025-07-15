<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use Database\Seeders\DatabaseSeeder;

class NpcNpcHealthsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Npc::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'npc_healths';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        NpcHealth::from(DatabaseSeeder::getTempTableName(NpcHealth::class))->insert($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
