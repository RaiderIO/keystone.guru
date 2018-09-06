<?php


class DungeonRouteRoutesRelationParser implements RelationParser
{
    /**
     * @param $modelClassName string
     * @return mixed
     */
    public function canParseModel($modelClassName)
    {
        return $modelClassName === '\App\Models\DungeonRoute';
    }

    /**
     * @param $name string
     * @param $value array
     * @return mixed
     */
    public function canParseRelation($name, $value)
    {
        return $name === 'routes' && is_array($value);
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
        foreach ($value as $routeData) {
            // We now know the dungeon route ID, set it back to the Route
            $routeData['dungeon_route_id'] = $modelData['id'];

            // Unset the relation data, otherwise the save function will complain that the column doesn't exist,
            // but keep a reference to it as we still need it later on
            $vertices = $routeData['vertices'];
            unset($routeData['vertices']);

            if( count($vertices) > 0 ){
                // Gotta save the Route in order to get an ID
                $route = new \App\Models\Route($routeData);
                $route->save();

                foreach ($vertices as $key => $vertex) {
                    // Make sure the vertex's relation with the route is restored.
                    // Do not use $vertex since that would create a new copy and we'd lose our changes
                    $vertices[$key]['route_id'] = $route->id;
                }

                // Insert vertices
                \App\Models\RouteVertex::insert($vertices);
            }
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}