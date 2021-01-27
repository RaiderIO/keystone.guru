<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnemyForcesColumnToDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('enemy_forces')->after('seasonal_index')->default(0);
        });

        // Set the enemy forces for all dungeon routes by calculating them
        DB::update('
        UPDATE dungeon_routes
            inner join (
                    select dungeon_routes.id,
                   CAST(IFNULL(
                           IF(dungeon_routes.teeming = 1,
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override_teeming >= 0,
                                              enemies.enemy_forces_override_teeming,
                                              IF(npcs.enemy_forces_teeming >= 0, npcs.enemy_forces_teeming, npcs.enemy_forces)
                                          )
                                  ),
                              SUM(
                                      IF(
                                              enemies.enemy_forces_override >= 0,
                                              enemies.enemy_forces_override,
                                              npcs.enemy_forces
                                          )
                                  )
                               ), 0
                       ) AS SIGNED)                  as enemy_forces,
                   count(distinct dungeon_routes.id) as aggregate
            from `dungeon_routes`
                     left join `kill_zones` on `kill_zones`.`dungeon_route_id` = `dungeon_routes`.`id`
                     left join `kill_zone_enemies` on `kill_zone_enemies`.`kill_zone_id` = `kill_zones`.`id`
                     left join `enemies` on `enemies`.`id` = `kill_zone_enemies`.`enemy_id`
                     left join `npcs` on `npcs`.`id` = `enemies`.`npc_id`
                     left join `dungeons` on `dungeons`.`id` = `dungeon_routes`.`dungeon_id`
            group by `dungeon_routes`.id
                ) ef ON dungeon_routes.id = ef.id
            SET dungeon_routes.enemy_forces = ef.enemy_forces
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropColumn('enemy_forces');
        });
    }
}
