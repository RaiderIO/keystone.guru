<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Spell\Spell;
use App\Models\Spell\SpellEffect;
use Database\Seeders\DatabaseSeeder;

class SpellSpellEffectsRelationParser implements RelationParserInterface
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
        return $name === 'spell_effects';
    }

    /**
     * @param  array<string, mixed> $modelData
     * @param  array<string, mixed> $value
     * @return array<string, mixed>
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        // The export hides the spell id on each effect, since the spell it belongs to is the row it is
        // nested under
        foreach ($value as &$spellEffect) {
            $spellEffect['spell_id'] = $modelData['id'];
        }

        SpellEffect::from(DatabaseSeeder::getTempTableName(SpellEffect::class))->insert($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
