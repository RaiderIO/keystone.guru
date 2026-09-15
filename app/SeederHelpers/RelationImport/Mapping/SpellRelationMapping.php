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
     * The combat-log-derived columns are no longer present in spells.json. Preserving them copies the
     * live values into the temp table before the swap, so a re-seed does not null the per-environment
     * combat-log data. Kept in lockstep with what the export hides via the shared constant - listing
     * them by hand here is what let `counters_mask` and `bypasses_immunities_mask` be wiped by every
     * deploy's `db:seed` (#4033).
     */
    #[\Override]
    public function getPreservedColumns(): array
    {
        return Spell::COMBAT_LOG_DERIVED_COLUMNS;
    }
}
