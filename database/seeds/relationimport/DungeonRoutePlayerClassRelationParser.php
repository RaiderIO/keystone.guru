<?php


class DungeonRoutePlayerClassRelationParser implements RelationParser
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
        return $name === 'playerclasses' && is_array($value);
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
        \App\Models\DungeonRoutePlayerClass::insert($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}