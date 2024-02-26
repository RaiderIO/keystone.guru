<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

class NestedModelRelationParser implements RelationParserInterface
{
    public function canParseRootModel(string $modelClassName): bool
    {
        return false;
    }

    public function canParseModel(string $modelClassName): bool
    {
        //  Can parse any model
        return true;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return isset($value['id']);
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        // Converts a relation like this: enemy: { id: 1, <otherattributes> } to enemy_id: 1 for saving
        if (! isset($modelData[$name.'_id'])) {
            $modelData[$name.'_id'] = $value['id'];
        }

        return $modelData;
    }
}
