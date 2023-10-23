<?php

use App\Models\Floor\FloorCoupling;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDirectionColumnToDungeonFloorSwitchMarkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->enum('direction', FloorCoupling::ALL)->nullable()->default(null)->after('target_floor_id');
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
            $table->dropColumn('direction');
        });
    }
}
