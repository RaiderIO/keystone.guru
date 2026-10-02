<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\MountableArea;
use App\Models\Polyline;

class MountableAreaPolylineRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === MountableArea::class;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'polyline';
    }

    /**
     * @param  array<string, mixed> $modelData
     * @param  array<string, mixed> $value
     * @return array<string, mixed>
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        // Make sure the polyline's relation with the model is restored.
        $value['model_class'] = $modelClassName;
        $value['model_id']    = $modelData['id'];

        $modelData['polyline_id'] = Polyline::insertGetId($value);

        return $modelData;
    }
}
