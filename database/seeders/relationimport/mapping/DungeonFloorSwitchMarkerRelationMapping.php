<?php


namespace Database\Seeders\RelationImport\Mapping;


use App\Models\DungeonFloorSwitchMarker;

class DungeonFloorSwitchMarkerRelationMapping extends RelationMapping
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct('dungeon_floor_switch_markers.json', DungeonFloorSwitchMarker::class);
    }
}