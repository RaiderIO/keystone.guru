<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\Spell\SpellTuningChange;

/**
 * Loads `spell_tuning_changes.json` - the build-over-build description changes `spell:difftuning`
 * computed on a dev machine. Every column is a scalar, so no attribute or relation parsers are needed;
 * the `id` is not in the file and is assigned on insert.
 */
class SpellTuningChangeRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('spell_tuning_changes.json', SpellTuningChange::class);
    }
}
