<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\Spell;

class SpellRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('spells.json', Spell::class);
    }
}
