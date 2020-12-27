<?php

use Illuminate\Database\Migrations\Migration;

class RemoveTeemingFromShadowlandsDungeonRoutes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('dungeon_routes')->where('dungeon_id', '>=', 28)->where('dungeon_id', '<=', 35)->update(['teeming' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No going back
    }
}
