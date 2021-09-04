<?php

namespace Database\Seeders\RelationImport\Parsers;

class DungeonRouteBrushlinesRelationParser implements RelationParser
{
    /**
     * @param $modelClassName string
     * @return mixed
     */
    public function canParseModel($modelClassName)
    {
        return $modelClassName === 'App\Models\DungeonRoute';
    }

    /**
     * @param $name string
     * @param $value array
     * @return mixed
     */
    public function canParseRelation($name, $value)
    {
        return $name === 'brushlines' && is_array($value);
    }

    /**
     * @param $modelClassName string
     * @param $modelData array
     * @param $name string
     * @param $value array
     * @return array
     */
    public function parseRelation($modelClassName, $modelData, $name, $value)
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
            $brushline = new \App\Models\Brushline($brushlineData);
            $brushline->save();

            $polyline['model_class'] = get_class($brushline);
            $polyline['model_id']    = $brushline->id;

            // Insert polyline, while capturing the result and coupling to the brushline
            $brushline->polyline_id = \App\Models\Polyline::insertGetId($polyline);
            $brushline->save();
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
