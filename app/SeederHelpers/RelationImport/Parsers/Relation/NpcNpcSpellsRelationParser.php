<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use Database\Seeders\DatabaseSeeder;

class NpcNpcSpellsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Npc::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'npc_spells';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        $npcSpellAttributes = [];
        foreach ($value as $npcSpell) {
            $npcSpell['npc_id'] = $modelData['id'];

            $npcSpellAttributes[] = $npcSpell;
        }
        NpcSpell::from(DatabaseSeeder::getTempTableName(NpcSpell::class))->insert($npcSpellAttributes);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
