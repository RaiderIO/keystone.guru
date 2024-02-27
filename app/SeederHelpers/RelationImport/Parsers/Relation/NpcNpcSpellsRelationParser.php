<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Npc;
use App\Models\NpcSpell;
use Database\Seeders\DatabaseSeeder;

class NpcNpcSpellsRelationParser implements RelationParserInterface
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
        return $name === 'npcspells';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $spell) {
            NpcSpell::from(DatabaseSeeder::getTempTableName(NpcSpell::class))->insert($spell);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
