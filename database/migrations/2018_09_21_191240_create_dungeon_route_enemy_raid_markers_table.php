<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDungeonRouteEnemyRaidMarkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dungeon_route_enemy_raid_markers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dungeon_route_id');
            $table->integer('enemy_id');
            $table->integer('raid_marker_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dungeon_route_enemy_raid_markers');
    }
}
