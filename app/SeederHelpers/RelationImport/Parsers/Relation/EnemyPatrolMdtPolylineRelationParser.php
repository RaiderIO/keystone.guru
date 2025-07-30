<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Logic\MDT\Entity\MDTPatrol;
use App\Models\EnemyPatrol;
use App\Models\Polyline;

class EnemyPatrolMdtPolylineRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === EnemyPatrol::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'mdt_polyline';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        // Make sure the polyline's relation with the model is restored.
        $value['model_class'] = MDTPatrol::class;
        $value['model_id']    = $modelData['id'];

        $modelData['mdt_polyline_id'] = Polyline::insertGetId($value);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
