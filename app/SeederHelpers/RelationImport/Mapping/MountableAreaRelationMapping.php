<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\MountableArea;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class MountableAreaRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('mountable_areas.json', MountableArea::class);

        $this->setConditionals(collect([
            new MappingVersionConditional()
        ]));
    }
}
