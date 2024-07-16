<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcBolsteringWhitelist;
use Database\Seeders\DatabaseSeeder;

class NpcNpcBolsteringWhitelistRelationParser implements RelationParserInterface
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
        return $name === 'npcbolsteringwhitelists';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        NpcBolsteringWhitelist::from(DatabaseSeeder::getTempTableName(NpcBolsteringWhitelist::class))->insert($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
