<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDungeonFloorSwitchMarkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('floor_id');
            $table->integer('target_floor_id');
            $table->float('lat');
            $table->float('lng');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dungeon_floor_switch_markers');
    }
}
