<?php

namespace Database\Seeders\RelationImport\Parsers;

class DungeonRouteKillZoneRelationParser implements RelationParser
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
        return $name === 'killzones' && is_array($value);
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
        foreach ($value as $killZoneData) {
            // We now know the dungeon route ID, set it back to the Route
            $killZoneData['dungeon_route_id'] = $modelData['id'];

            // Unset the relation data, otherwise the save function will complain that the column doesn't exist,
            // but keep a reference to it as we still need it later on
            $enemies = $killZoneData['killzoneenemies'];
            unset($killZoneData['killzoneenemies']);

            if (count($enemies) > 0) {
                // Gotta save the KillZone in order to get an ID
                $killZone = new \App\Models\KillZone($killZoneData);
                $killZone->save();

                foreach ($enemies as $key => $enemy) {
                    // Make sure the enemy's relation with the kill zone is restored.
                    // Do not use $enemy since that would create a new copy and we'd lose our changes
                    $enemies[$key]['kill_zone_id'] = $killZone->id;
                }

                // Insert vertices
                \App\Models\KillZoneEnemy::insert($enemies);
            }
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}