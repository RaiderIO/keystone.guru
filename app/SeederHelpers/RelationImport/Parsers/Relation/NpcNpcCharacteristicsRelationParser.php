<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use Database\Seeders\DatabaseSeeder;

class NpcNpcCharacteristicsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Npc::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'npc_characteristics';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $npcCharacteristic) {
            $npcCharacteristic['npc_id'] = $modelData['id'];

            NpcCharacteristic::from(DatabaseSeeder::getTempTableName(NpcCharacteristic::class))->insert($npcCharacteristic);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
