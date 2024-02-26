<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;

class DungeonRoutePlayerRaceRelationParser implements RelationParserInterface
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
        return $name === 'playerraces';
    }

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $playerRace) {
            // We now know the dungeon route ID, set it back to the player race
            $playerRace['dungeon_route_id'] = $modelData['id'];

            DungeonRoutePlayerRace::insert($playerRace);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
