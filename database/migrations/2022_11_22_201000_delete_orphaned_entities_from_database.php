<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteOrphanedEntitiesFromDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::delete('
            DELETE `polylines`
            FROM polylines
                LEFT JOIN brushlines on polylines.id = brushlines.polyline_id
            WHERE model_class = "App\\Models\\Brushline" AND brushlines.id is null;
        ');

        DB::delete('
            DELETE `polylines`
            FROM polylines
                 LEFT JOIN paths on polylines.id = paths.polyline_id
            WHERE model_class = "App\\Models\\Path" AND paths.id is null;
        ');

        DB::delete('
                DELETE `kill_zone_enemies` FROM `kill_zone_enemies`
                LEFT JOIN `kill_zones` ON `kill_zones`.`id` = `kill_zone_enemies`.`kill_zone_id`
                LEFT JOIN `dungeon_routes` ON `dungeon_routes`.`id` = `kill_zones`.`dungeon_route_id`
                WHERE `dungeon_routes`.`id` is null;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('database', function (Blueprint $table) {
            //
        });
    }
}
