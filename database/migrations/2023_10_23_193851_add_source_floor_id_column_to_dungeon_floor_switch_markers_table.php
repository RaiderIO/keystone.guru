<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceFloorIdColumnToDungeonFloorSwitchMarkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->integer('source_floor_id')->nullable()->default(null)->after('floor_id');

            $table->index(['source_floor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->dropColumn('source_floor_id');
        });
    }
}
