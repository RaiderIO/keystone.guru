<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAttribute;

class DungeonRouteAttributesRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonRoute::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'routeattributesraw';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $attribute) {
            // We now know the dungeon route ID, set it back to the player class
            $attribute['dungeon_route_id'] = $modelData['id'];

            DungeonRouteAttribute::insert($attribute);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
