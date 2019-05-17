<?php


class DungeonRoutePathsRelationParser implements RelationParser
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
        return $name === 'paths' && is_array($value);
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
        foreach ($value as $pathData) {
            // We now know the dungeon route ID, set it back to the Path
            $pathData['dungeon_route_id'] = $modelData['id'];
            // Set a default value
            $pathData['polyline_id'] = -1;

            // Unset the relation data, otherwise the save function will complain that the column doesn't exist,
            // but keep a reference to it as we still need it later on
            $polyline = $pathData['polyline'];
            unset($pathData['polyline']);

            // Gotta save the Path in order to get an ID
            $path = new \App\Models\Path($pathData);
            $path->save();

            $polyline['model_class'] = get_class($path);
            $polyline['model_id'] = $path->id;

            // Insert polyline, while capturing the result and coupling to the path
            $path->polyline_id = \App\Models\Polyline::insertGetId($polyline);
            $path->save();
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}