<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\DungeonStart;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class DungeonStartRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('dungeon_starts.json', DungeonStart::class);

        $this->setConditionals(collect([
            new MappingVersionConditional(),
        ]));
    }
}
