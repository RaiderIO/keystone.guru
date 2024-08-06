<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use Database\Seeders\DatabaseSeeder;

class SpellSpellDungeonsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Spell::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'spell_dungeons';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        $spellDungeonAttributes = [];
        foreach ($value as $spellDungeon) {
            $spellDungeon['spell_id'] = $modelData['id'];

            $spellDungeonAttributes[] = $spellDungeon;
        }

        SpellDungeon::from(DatabaseSeeder::getTempTableName(SpellDungeon::class))->insert($spellDungeonAttributes);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
