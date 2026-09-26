<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\Spell\SpellTuningBuild;

/**
 * Loads `spell_tuning_builds.json` - every client build `spell:difftuning` compared, including builds
 * that changed nothing. Every column is a scalar, so no attribute or relation parsers are needed; the
 * `id` is not in the file and is assigned on insert.
 */
class SpellTuningBuildRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('spell_tuning_builds.json', SpellTuningBuild::class);
    }
}
