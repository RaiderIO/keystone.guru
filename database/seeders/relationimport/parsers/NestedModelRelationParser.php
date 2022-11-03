<?php

namespace Database\Seeders\RelationImport\Parsers;

class NestedModelRelationParser implements RelationParser
{
    /**
     * @param string $modelClassName
     * @return bool
     */
    public function canParseModel(string $modelClassName): bool
    {
        //  Can parse any model
        return true;
    }

    /**
     * @param string $name
     * @param array $value
     * @return bool
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return isset($value['id']);
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
        // Converts a relation like this: enemy: { id: 1, <otherattributes> } to enemy_id: 1 for saving
        if (!isset($modelData[$name . '_id'])) {
            $modelData[$name . '_id'] = $value['id'];
        }

        return $modelData;
    }

}
