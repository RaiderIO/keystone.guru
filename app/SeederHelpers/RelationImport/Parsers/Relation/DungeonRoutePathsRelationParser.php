<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapObjectToAwakenedObeliskLink;
use App\Models\Path;
use App\Models\Polyline;

class DungeonRoutePathsRelationParser implements RelationParserInterface
{
    public function canParseRootModel(string $modelClassName): bool
    {
        return false;
    }

    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonRoute::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'paths';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $pathData) {
            // We now know the dungeon route ID, set it back to the Path
            $pathData['dungeon_route_id'] = $modelData['id'];
            // Set a default value
            $pathData['polyline_id'] = -1;

            // Unset the relation data, otherwise the save function will complain that the column doesn't exist,
            // but keep a reference to it as we still need it later on
            $polyline = $pathData['polyline'];
            unset($pathData['polyline']);
            // Ditto awakened obelisks
            $awakenedObeliskLinkData = $pathData['linkedawakenedobelisks'];
            unset($pathData['linkedawakenedobelisks']);

            // Gotta save the Path in order to get an ID
            $path = new Path($pathData);
            $path->save();

            $polyline['model_class'] = $path::class;
            $polyline['model_id'] = $path->id;

            // Insert polyline, while capturing the result and coupling to the path
            $path->polyline_id = Polyline::insertGetId($polyline);
            $path->save();

            // Restore awakened obelisk data
            foreach ($awakenedObeliskLinkData as $data) {
                $data['source_map_object_id'] = $path->id;
                $data['source_map_object_class_name'] = $path::class;
                MapObjectToAwakenedObeliskLink::insert($data);
            }
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
