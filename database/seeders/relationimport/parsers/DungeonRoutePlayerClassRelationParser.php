<?php

namespace Database\Seeders\RelationImport\Parsers;

use App\Models\DungeonRoute;
use App\Models\DungeonRoutePlayerClass;

class DungeonRoutePlayerClassRelationParser implements RelationParser
{
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
        return $name === 'playerclasses';
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
        foreach ($value as $playerClass) {
            // We now know the dungeon route ID, set it back to the player class
            $playerClass['dungeon_route_id'] = $modelData['id'];

            DungeonRoutePlayerClass::insert($playerClass);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
