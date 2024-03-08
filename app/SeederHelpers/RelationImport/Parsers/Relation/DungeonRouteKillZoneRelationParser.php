<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use Database\Seeders\DatabaseSeeder;
use Exception;

class DungeonRouteKillZoneRelationParser implements RelationParserInterface
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
        return $name === 'killzones' || $name === 'kill_zones';
    }

    /**
     * @throws Exception
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        $allEnemyIds = [];

        foreach ($value as $killZoneData) {

            $enemyIds = $killZoneData['enemies'];
            foreach ($enemyIds as $key => $enemyId) {
                $allEnemyIds = array_merge($allEnemyIds, $enemyIds);
            }
        }

        // Cache all enemies that we need to resolve the enemies for this route
        $enemies = Enemy::from(DatabaseSeeder::getTempTableName(Enemy::class))->whereIn('id', $allEnemyIds)->get()->keyBy('id');

        $killZoneEnemyAttributes = [];
        $killZoneSpellAttributes = [];
        foreach ($value as $killZoneData) {
            // We now know the dungeon route ID, set it back to the Route
            $killZoneData['dungeon_route_id'] = $modelData['id'];

            // Unset the relation data, otherwise the save function will complain that the column doesn't exist,
            // but keep a reference to it as we still need it later on

            $enemyIds = $killZoneData['enemies'];
            unset($killZoneData['enemies']);

            $spells = $killZoneData['spells'];
            unset($killZoneData['spells']);

            // Gotta save the KillZone in order to get an ID
            $killZone = KillZone::create($killZoneData);

            if (count($enemyIds) > 0) {

                $savedEnemyIds = collect();

                foreach ($enemyIds as $key => $enemyId) {
                    // Do not doubly save enemies if the file somehow contained doubles (#1473)
                    if ($savedEnemyIds->contains($enemyId)) {
                        continue;
                    }

                    $enemy = $enemies->get($enemyId);
                    if ($enemy === null) {
                        throw new Exception(sprintf('Unable to find enemy with id %s', $enemyId));
                    }

                    // Make sure the enemy's relation with the kill zone is restored.
                    $killZoneEnemyAttributes[] = [
                        'kill_zone_id' => $killZone->id,
                        'npc_id'       => $enemy->npc_id,
                        'mdt_id'       => $enemy->mdt_id,
                    ];

                    $savedEnemyIds->push($enemyId);
                }
            }

            if (count($spells) > 0) {

                $savedSpells = collect();

                foreach ($spells as $key => $spellId) {
                    // Do not doubly save spells if the file somehow contained doubles (#1473)
                    if ($savedSpells->contains($spellId)) {
                        continue;
                    }

                    // Make sure the spell's relation with the kill zone is restored.
                    $killZoneSpellAttributes[] = [
                        'kill_zone_id' => $killZone->id,
                        'spell_id'     => $spellId,
                    ];
                }
            }
        }

        KillZoneEnemy::insert($killZoneEnemyAttributes);
        KillZoneSpell::insert($killZoneSpellAttributes);

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
