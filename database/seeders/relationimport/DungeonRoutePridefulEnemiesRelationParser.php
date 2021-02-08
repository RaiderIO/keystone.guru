<?php

namespace Database\Seeders\RelationImport;

use App\Models\MapIcon;
use App\Models\MapObjectToAwakenedObeliskLink;

class DungeonRoutePridefulEnemiesRelationParser implements RelationParser
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
        return $name === 'pridefulenemies' && is_array($value);
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
        foreach ($value as $pridefulEnemies) {
            // We now know the dungeon route ID, set it back to the Route
            $pridefulEnemies['dungeon_route_id'] = $modelData['id'];
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}