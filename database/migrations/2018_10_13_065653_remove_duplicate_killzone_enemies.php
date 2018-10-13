<?php

use Illuminate\Database\Migrations\Migration;

class RemoveDuplicateKillzoneEnemies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up()
    {
        $dungeonroutes = \App\Models\DungeonRoute::all();
        foreach ($dungeonroutes as $dungeonroute) {
            /** @var $dungeonroute \App\Models\DungeonRoute */
            foreach ($dungeonroute->killzones as $killzone) {
                /** @var \App\Models\KillZone $killzone */
                foreach ($killzone->killzoneenemies as $killzoneenemy) {
                    foreach ($killzone->killzoneenemies as $killzoneenemyB) {
                        /** @var \App\Models\KillZoneEnemy $killzoneenemy */
                        /** @var \App\Models\KillZoneEnemy $killzoneenemyB */

                        // If not comparing the same killzone, they carry the same enemy and haven't been deleted yet before
                        if ($killzoneenemy->id !== $killzoneenemyB->id &&
                            $killzoneenemy->enemy_id === $killzoneenemyB->enemy_id &&
                            $killzoneenemy->exists && $killzoneenemyB->exists) {
                            // Delete the 2nd enemy
                            $killzoneenemyB->delete();
                        }
                    }
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
