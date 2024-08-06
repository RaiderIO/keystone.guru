<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;

class DungeonRoutePridefulEnemiesRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonRoute::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'prideful_enemies';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $pridefulEnemies) {
            // We now know the dungeon route ID, set it back to the Route
            $pridefulEnemies['dungeon_route_id'] = $modelData['id'];
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
