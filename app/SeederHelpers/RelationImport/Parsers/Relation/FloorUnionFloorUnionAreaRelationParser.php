<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Floor\FloorUnion;

class FloorUnionFloorUnionAreaRelationParser implements RelationParserInterface
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
        return $modelClassName === FloorUnion::class;
    }

    /**
     * @param string $name
     * @param array  $value
     * @return bool
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'floor_union_areas';
    }

    /**
     * @param string $modelClassName
     * @param array  $modelData
     * @param string $name
     * @param array  $value
     * @return array
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        if (!isset($modelData['floor_union_id'])) {
            $modelData['floor_union_id'] = $value['id'];
        }

        return $modelData;
    }

}
