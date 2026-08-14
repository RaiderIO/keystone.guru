<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\Spell\Spell;
use App\SeederHelpers\RelationImport\Parsers\Attribute\JsonAttributeParser;
use App\SeederHelpers\RelationImport\Parsers\Attribute\TimestampAttributeParser;
use App\SeederHelpers\RelationImport\Parsers\Relation\SpellSpellEffectsRelationParser;

class SpellRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('spells.json', Spell::class);

        $this->setAttributeParsers(collect([
            new TimestampAttributeParser(),
            new JsonAttributeParser(['description_values']),
        ]));

        $this->setPreSaveRelationParsers(collect([
            new SpellSpellEffectsRelationParser(),
        ]));
    }

    /**
     * aura, debuff and miss_types_mask are combat-log-derived behavior which is no longer present in
     * spells.json. Preserving them copies the live values into the temp table before the swap, so a
     * re-seed does not null the per-environment combat-log data.
     */
    #[\Override]
    public function getPreservedColumns(): array
    {
        return ['aura', 'debuff', 'miss_types_mask'];
    }
}
