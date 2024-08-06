<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\Spell\Spell;
use App\SeederHelpers\RelationImport\Parsers\Relation\SpellSpellDungeonsRelationParser;

class SpellRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('spells.json', Spell::class);

        $this->setPreSaveRelationParsers(collect([
            new SpellSpellDungeonsRelationParser(),
        ]));
    }
}
