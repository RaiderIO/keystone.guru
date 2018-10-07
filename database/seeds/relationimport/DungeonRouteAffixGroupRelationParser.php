<?php


class DungeonRouteAffixGroupRelationParser implements RelationParser
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
        return $name === 'affixgroups' && is_array($value);
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
        foreach ($value as $affixGroup) {
            // We now know the dungeon route ID, set it back to the Route
            $affixGroup['dungeon_route_id'] = $modelData['id'];

            \App\Models\DungeonRouteAffixGroup::insert($affixGroup);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}