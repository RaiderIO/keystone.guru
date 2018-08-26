<?php


class EnemyPatrolVerticesRelationParser implements RelationParser
{
    /**
     * @param $modelClassName string
     * @return mixed
     */
    public function canParseModel($modelClassName)
    {
        return $modelClassName === '\App\Models\EnemyPatrol';
    }

    /**
     * @param $name string
     * @param $value array
     * @return mixed
     */
    public function canParseRelation($name, $value)
    {
        return $name === 'vertices' && is_array($value);
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
        foreach ($value as $key => $vertex) {
            // Make sure the vertex's relation with the enemy patrol is restored.
            // Do not use $vertex since that would create a new copy and we'd lose our changes
            $value[$key]['enemy_patrol_id'] = $modelData['id'];
        }

        \App\Models\EnemyPatrolVertex::insert($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}