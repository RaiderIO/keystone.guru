<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteOrphansFromKillZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::delete('
            DELETE `kill_zones`
            FROM kill_zones
                LEFT JOIN `dungeon_routes` ON `dungeon_routes`.`id` = `kill_zones`.`dungeon_route_id`
            WHERE `dungeon_routes`.id is null;
        ');

        DB::delete('
            DELETE `kill_zone_enemies`
            FROM kill_zone_enemies
                LEFT JOIN `kill_zones` ON `kill_zones`.`id` = `kill_zone_enemies`.`kill_zone_id`
            WHERE `kill_zones`.id is null;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kill_zones', function (Blueprint $table) {
            //
        });
    }
}
