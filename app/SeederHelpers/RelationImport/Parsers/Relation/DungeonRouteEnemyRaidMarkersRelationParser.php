<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use Database\Seeders\DatabaseSeeder;
use Exception;
use Illuminate\Support\Collection;

class DungeonRouteEnemyRaidMarkersRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === DungeonRoute::class;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'enemyraidmarkers' || $name === 'enemy_raid_markers';
    }

    /**
     * @param  array<string, mixed> $modelData
     * @param  array<string, mixed> $value
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        $enemyIds = array_column($value, 'enemy_id');

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::from(DatabaseSeeder::getTempTableName(Enemy::class))->whereIn('id', $enemyIds)->get()->keyBy('id');

        foreach ($value as $enemyRaidMarkerData) {
            $enemy = $enemies->get($enemyRaidMarkerData['enemy_id']);
            if ($enemy === null) {
                throw new Exception(sprintf('Unable to find enemy with id %s', $enemyRaidMarkerData['enemy_id']));
            }

            // We now know the dungeon route ID, set it back to the Route
            $enemyRaidMarkerData['dungeon_route_id'] = $modelData['id'];
            // Resolve npc_id/mdt_id from the freshly-seeded enemy rather than trusting the raw
            // enemy_id in the fixture, so a later mapping version upgrade can still find it (#1453)
            $enemyRaidMarkerData['npc_id'] = $enemy->getMdtNpcId();
            $enemyRaidMarkerData['mdt_id'] = $enemy->mdt_id;

            DungeonRouteEnemyRaidMarker::insert($enemyRaidMarkerData);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
