<?php

namespace Database\Seeders\RelationImport;

class EnemyPatrolPolylineRelationParser implements RelationParser
{
    /**
     * @param $modelClassName string
     * @return mixed
     */
    public function canParseModel($modelClassName)
    {
        return $modelClassName === 'App\Models\EnemyPatrol';
    }

    /**
     * @param $name string
     * @param $value array
     * @return mixed
     */
    public function canParseRelation($name, $value)
    {
        return $name === 'polyline';
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
        // Make sure the polyline's relation with the model is restored.
        $value['model_class'] = $modelClassName;
        $value['model_id'] = $modelData['id'];

        $modelData['polyline_id'] = \App\Models\Polyline::insertGetId($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}