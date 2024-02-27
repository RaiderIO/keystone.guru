<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;

class DungeonRouteEnemyRaidMarkersRelationParser implements RelationParserInterface
{
    public function canParseRootModel(string $modelClassName): bool
    {
        return false;
    }

    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonRoute::class;
    }

    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'enemyraidmarkers' || $name === 'enemy_raid_markers';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $enemyRaidMarkerData) {
            // We now know the dungeon route ID, set it back to the Route
            $enemyRaidMarkerData['dungeon_route_id'] = $modelData['id'];

            DungeonRouteEnemyRaidMarker::insert($enemyRaidMarkerData);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
