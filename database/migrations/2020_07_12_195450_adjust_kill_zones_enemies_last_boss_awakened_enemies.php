<?php

use Illuminate\Database\Migrations\Migration;

class AdjustKillZonesEnemiesLastBossAwakenedEnemies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $killZonesToUpdate = DB::select('
            select kill_zones.*, dungeon_routes.dungeon_id
            FROM kill_zones
            LEFT JOIN kill_zone_enemies kze on kill_zones.id = kze.kill_zone_id
            LEFT JOIN enemies e on kze.enemy_id = e.id
            LEFT JOIN npcs n on e.npc_id = n.id
            LEFT JOIN dungeon_routes on kill_zones.dungeon_route_id = dungeon_routes.id
            WHERE n.classification_id = 4
        ');

        $awakenedEnemiesData = collect(DB::select('
        SELECT e.id, e.seasonal_index, e.enemy_pack_id, f.id as floor_id, d.id as dungeon_id, n.id as npc_id, n.name as npc_name
        FROM enemies e
        LEFT JOIN npcs n on e.npc_id = n.id
        LEFT JOIN floors f on e.floor_id = f.id
        LEFT JOIN dungeons d on f.dungeon_id = d.id
        WHERE d.active = 1
        AND n.id IN (161124, 161241, 161244, 161243)
        ORDER BY n.id, enemy_pack_id
        '))->groupBy('dungeon_id');

        // Format the data so we can access it easier
        $awakenedEnemiesMappingByDungeon = [];
        foreach ($awakenedEnemiesData as $dungeonId => $awakenedEnemyByDungeon) {
            // Create new dungeon entry
            $awakenedEnemiesMappingByDungeon[$dungeonId] = [];
            // For all enemies
            foreach ($awakenedEnemyByDungeon as $awakenedEnemyData) {
                $key = $awakenedEnemyData->enemy_pack_id === -1 ? 'old' : 'new';
                $npcId = $awakenedEnemyData->npc_id;
                $enemyId = $awakenedEnemyData->id;
                $seasonalIndex = $awakenedEnemyData->seasonal_index;

                // Make a new entry for this NPC ID
                if (!isset($awakenedEnemiesMappingByDungeon[$dungeonId])) {
                    $awakenedEnemiesMappingByDungeon[$dungeonId][$npcId] = [];
                    $awakenedEnemiesMappingByDungeon[$dungeonId][$npcId][$seasonalIndex] = [];
                }

                // dungeonID => npcID => new/old => $enemyId
                $awakenedEnemiesMappingByDungeon[$dungeonId][$npcId][$seasonalIndex][$key] = $enemyId;
            }
        }

        // @formatter:off
        /** Array will look like this:
           array:1 [
              14 => array:4 [
                161124 => array:1 [
                  "" => array:2 [
                    "old" => 4166
                    "new" => 4282
                  ]
                ]
                161241 => array:2 [
                  1 => array:2 [
                    "old" => 4165
                    "new" => 4285
                  ]
                  0 => array:2 [
                    "old" => 4221
                    "new" => 4281
                  ]
                ]
                161243 => array:1 [
                  "" => array:2 [
                    "old" => 4164
                    "new" => 4280
                  ]
                ]
                161244 => array:2 [
                  1 => array:2 [
                    "old" => 4167
                    "new" => 4283
                  ]
                  0 => array:2 [
                    "old" => 4222
                    "new" => 4284
                  ]
                ]
              ]
            ]
         */
        // @formatter:on

        // Perform the migration
        foreach ($killZonesToUpdate as $killZoneToUpdate) {
            $awakenedEnemiesDungeonData = $awakenedEnemiesMappingByDungeon[$killZoneToUpdate->dungeon_id];

            foreach($awakenedEnemiesDungeonData as $npcId => $npcData ){
                foreach($npcData as $seasonalIndex => $replacementData ){
                    DB::update("
                        UPDATE kill_zone_enemies
                        LEFT JOIN kill_zones ON kill_zone_enemies.kill_zone_id = kill_zones.id
                        SET kill_zone_enemies.enemy_id = {$replacementData['new']}
                        WHERE kill_zone_id = {$killZoneToUpdate->id}
                        AND kill_zone_enemies.enemy_id = {$replacementData['old']}
                        ");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
