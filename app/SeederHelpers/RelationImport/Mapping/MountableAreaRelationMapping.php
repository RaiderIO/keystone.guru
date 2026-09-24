<?php

namespace App\SeederHelpers\RelationImport\Mapping;

use App\Models\MountableArea;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;
use App\SeederHelpers\RelationImport\Parsers\Relation\MountableAreaPolylineRelationParser;

class MountableAreaRelationMapping extends RelationMapping
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('mountable_areas.json', MountableArea::class);

        $this->setConditionals(collect([
            new MappingVersionConditional(),
        ]));
        $this->setPreSaveRelationParsers(collect([
            new MountableAreaPolylineRelationParser(),
        ]));
    }
}
