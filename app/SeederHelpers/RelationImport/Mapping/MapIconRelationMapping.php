<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\MapIcon;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class MapIconRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('map_icons.json', MapIcon::class);

        $this->setConditionals(collect([
            new MappingVersionConditional()
        ]));
    }
}
