<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\EnemyPatrol;
use App\Models\Polyline;

class EnemyPatrolPolylineRelationParser implements RelationParserInterface
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
        return $modelClassName === EnemyPatrol::class;
    }

    /**
     * @param string $name
     * @param array $value
     * @return bool
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'polyline';
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
        // Make sure the polyline's relation with the model is restored.
        $value['model_class'] = $modelClassName;
        $value['model_id']    = $modelData['id'];

        $modelData['polyline_id'] = Polyline::insertGetId($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
