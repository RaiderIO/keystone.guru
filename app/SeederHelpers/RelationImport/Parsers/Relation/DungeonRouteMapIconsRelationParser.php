<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapObjectToAwakenedObeliskLink;

class DungeonRouteMapIconsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonRoute::class;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'mapicons';
    }

    /**
     * @param  array<string, mixed> $modelData
     * @param  array<string, mixed> $value
     * @return array<string, mixed>
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $mapIconData) {
            // We now know the dungeon route ID, set it back to the map comment
            $mapIconData['dungeon_route_id'] = $modelData['id'];

            $awakenedObeliskLinkData = $mapIconData['linkedawakenedobelisks'];
            unset($mapIconData['linkedawakenedobelisks']);

            $mapIcon = MapIcon::create($mapIconData);

            // Restore awakened obelisk data
            foreach ($awakenedObeliskLinkData as $data) {
                $data['source_map_object_id']         = $mapIcon->id;
                $data['source_map_object_class_name'] = $mapIcon::class;
                MapObjectToAwakenedObeliskLink::insert($data);
            }
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
