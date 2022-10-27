<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Brushline;
use App\Models\DungeonRoute;
use App\Models\Polyline;

class DungeonRouteBrushlinesRelationParser implements RelationParserInterface
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
        return $name === 'brushlines';
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
        foreach ($value as $brushlineData) {
            // We now know the dungeon route ID, set it back to the Path
            $brushlineData['dungeon_route_id'] = $modelData['id'];
            // Set a default value
            $brushlineData['polyline_id'] = -1;

            // Unset the relation data, otherwise the save function will complain that the column doesn't exist,
            // but keep a reference to it as we still need it later on
            $polyline = $brushlineData['polyline'];
            unset($brushlineData['polyline']);

            // Gotta save the Brushline in order to get an ID
            $brushline = new Brushline($brushlineData);
            $brushline->save();

            $polyline['model_class'] = get_class($brushline);
            $polyline['model_id']    = $brushline->id;

            // Insert polyline, while capturing the result and coupling to the brushline
            $brushline->polyline_id = Polyline::insertGetId($polyline);
            $brushline->save();
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
