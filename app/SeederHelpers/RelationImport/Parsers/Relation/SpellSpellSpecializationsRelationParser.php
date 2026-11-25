<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Spell\Spell;
use App\Models\Spell\SpellSpecialization;
use Database\Seeders\DatabaseSeeder;

class SpellSpellSpecializationsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Spell::class;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'spell_specializations';
    }

    /**
     * @param  array<string, mixed> $modelData
     * @param  array<string, mixed> $value
     * @return array<string, mixed>
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        // The export hides the spell id on each row, since the spell it belongs to is the row it is nested under
        foreach ($value as &$spellSpecialization) {
            $spellSpecialization['spell_id'] = $modelData['id'];
        }

        SpellSpecialization::from(DatabaseSeeder::getTempTableName(SpellSpecialization::class))->insert($value);

        return $modelData;
    }
}
