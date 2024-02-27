<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;

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

    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $killZoneData) {
            // We now know the dungeon route ID, set it back to the Route
            $killZoneData['dungeon_route_id'] = $modelData['id'];

            // Unset the relation data, otherwise the save function will complain that the column doesn't exist,
            // but keep a reference to it as we still need it later on

            $enemies = $killZoneData['enemies'];
            unset($killZoneData['enemies']);

            $spells = $killZoneData['spells'];
            unset($killZoneData['spells']);

            // Gotta save the KillZone in order to get an ID
            $killZone = KillZone::create($killZoneData);

            if (count($enemies) > 0) {
                $killZoneEnemyAttributes = [];

                $savedEnemyIds = collect();

                foreach ($enemies as $key => $enemyId) {
                    // Do not doubly save enemies if the file somehow contained doubles (#1473)
                    if ($savedEnemyIds->contains($enemyId)) {
                        continue;
                    }

                    $enemy = Enemy::findOrFail($enemyId);
                    // Make sure the enemy's relation with the kill zone is restored.
                    $killZoneEnemyAttributes[] = [
                        'kill_zone_id' => $killZone->id,
                        'npc_id'       => $enemy->npc_id,
                        'mdt_id'       => $enemy->mdt_id,
                    ];

                    $savedEnemyIds->push($enemyId);
                }

                KillZoneEnemy::insert($killZoneEnemyAttributes);
            }

            if (count($spells) > 0) {
                $killZoneSpellAttributes = [];

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

                KillZoneSpell::insert($killZoneSpellAttributes);
            }
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
