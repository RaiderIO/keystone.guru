<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeTargetFloorIdColumnNullableInDungeonFloorSwitchMarkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->integer('target_floor_id')->nullable()->default(null)->change();
        });

        DB::update('UPDATE `dungeon_floor_switch_markers` SET `target_floor_id` = null WHERE `target_floor_id` <= 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->integer('target_floor_id')->nullable(false)->default(-1)->change();
        });
    }
}
