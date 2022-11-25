<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;

class DungeonRouteKillZoneRelationParser implements RelationParserInterface
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
        return $name === 'killzones';
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
        foreach ($value as $killZoneData) {
            // We now know the dungeon route ID, set it back to the Route
            $killZoneData['dungeon_route_id'] = $modelData['id'];

            // Unset the relation data, otherwise the save function will complain that the column doesn't exist,
            // but keep a reference to it as we still need it later on

            $enemies = $killZoneData['enemies'];
            unset($killZoneData['enemies']);

            if (count($enemies) > 0) {
                // Gotta save the KillZone in order to get an ID
                $killZone                = KillZone::create($killZoneData);
                $killZoneEnemyAttributes = [];

                $savedEnemyIds = collect();

                foreach ($enemies as $key => $enemyId) {
                    // Do not doubly save enemies if the file somehow contained doubles (#1473)
                    if ($savedEnemyIds->contains($enemyId)) {
                        continue;
                    }

                    $enemy = Enemy::findOrFail($enemyId);
                    // Make sure the enemy's relation with the kill zone is restored.
                    // Do not use $enemy since that would create a new copy and we'd lose our changes
                    $killZoneEnemyAttributes[] = [
                        'kill_zone_id' => $killZone->id,
                        'npc_id'       => $enemy->npc_id,
                        'mdt_id'       => $enemy->mdt_id,
                    ];

                    $savedEnemyIds->push($enemyId);
                }

                // Insert vertices
                KillZoneEnemy::insert($killZoneEnemyAttributes);
            }
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
