<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;

class DungeonRouteAffixGroupRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonRoute::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'affixgroups';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $affixGroup) {
            // We now know the dungeon route ID, set it back to the Route
            $affixGroup['dungeon_route_id'] = $modelData['id'];

            DungeonRouteAffixGroup::insert($affixGroup);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
