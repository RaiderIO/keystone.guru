<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapObjectToAwakenedObeliskLink;

class DungeonRouteMapIconsRelationParser implements RelationParserInterface
{
    /**
     * @param string $modelClassName
     * @return bool
     */
    public function canParseRootModel(string $modelClassName): bool
    {
        return false;
    }

    /**
     * @param string $modelClassName
     * @return bool
     */
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonRoute::class;
    }

    /**
     * @param string $name
     * @param array $value
     * @return bool
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'mapicons';
    }

    /**
     * @param string $modelClassName
     * @param array $modelData
     * @param string $name
     * @param array $value
     * @return array
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $mapIconData) {
            // We now know the dungeon route ID, set it back to the map comment
            $mapIconData['dungeon_route_id'] = $modelData['id'];

            $awakenedObeliskLinkData = $mapIconData['linkedawakenedobelisks'];
            unset($mapIconData['linkedawakenedobelisks']);

            $mapIcon = new MapIcon($mapIconData);
            $mapIcon->save();

            // Restore awakened obelisk data
            foreach ($awakenedObeliskLinkData as $data) {
                $data['source_map_object_id']         = $mapIcon->id;
                $data['source_map_object_class_name'] = get_class($mapIcon);
                MapObjectToAwakenedObeliskLink::insert($data);
            }
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
