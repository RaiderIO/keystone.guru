<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\Floor\FloorUnionArea;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class FloorUnionAreaRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('floor_union_areas.json', FloorUnionArea::class);

        $this->setConditionals(collect([
            new MappingVersionConditional()
        ]));
    }
}
