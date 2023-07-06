<?php


namespace App\SeederHelpers\RelationImport\Mapping;


use App\Models\DungeonFloorSwitchMarker;
use App\SeederHelpers\RelationImport\Conditionals\MappingVersionConditional;

class DungeonFloorSwitchMarkerRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('dungeon_floor_switch_markers.json', DungeonFloorSwitchMarker::class);

        $this->setConditionals(collect([
            new MappingVersionConditional()
        ]));
    }
}
