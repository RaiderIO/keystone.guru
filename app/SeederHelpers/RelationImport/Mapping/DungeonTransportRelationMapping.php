<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\DungeonTransport;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class DungeonTransportRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('dungeon_transports.json', DungeonTransport::class);

        $this->setConditionals(collect([
            new MappingVersionConditional(),
        ]));
    }
}
