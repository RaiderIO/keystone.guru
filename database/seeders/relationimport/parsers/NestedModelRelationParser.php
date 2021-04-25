<?php

namespace Database\Seeders\RelationImport\Parsers;

class NestedModelRelationParser implements RelationParser
{
    /**
     * @param $modelClassName
     * @return mixed
     */
    public function canParseModel($modelClassName)
    {
        //  Can parse any model
        return true;
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function canParseRelation($name, $value)
    {
        return is_array($value) && isset($value['id']);
    }

    /**
     * @param $modelClassName
     * @param $modelData
     * @param $name
     * @param $value
     * @return mixed
     */
    public function parseRelation($modelClassName, $modelData, $name, $value)
    {
        // Converts a relation like this: enemy: { id: 1, <otherattributes> } to enemy_id: 1 for saving
        if (!isset($modelData[$name . '_id'])) {
            $modelData[$name . '_id'] = $value['id'];
        }

        return $modelData;
    }

}