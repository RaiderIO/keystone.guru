<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\Floor\FloorUnion;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class FloorUnionRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('floor_unions.json', FloorUnion::class);

        $this->setConditionals(collect([
            new MappingVersionConditional()
        ]));
    }
}
