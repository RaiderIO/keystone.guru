<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute;
use App\Models\DungeonRouteEnemyRaidMarker;

class DungeonRouteEnemyRaidMarkersRelationParser implements RelationParserInterface
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
        return $modelClassName === DungeonRoute::class;
    }

    /**
     * @param string $name
     * @param array $value
     * @return bool
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'enemyraidmarkers';
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
        foreach ($value as $enemyRaidMarkerData) {
            // We now know the dungeon route ID, set it back to the Route
            $enemyRaidMarkerData['dungeon_route_id'] = $modelData['id'];

            DungeonRouteEnemyRaidMarker::insert($enemyRaidMarkerData);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
