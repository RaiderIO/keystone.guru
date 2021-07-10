<?php

use Illuminate\Database\Migrations\Migration;

class UnpublishNonShadowlandsDungeonRoutes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::table('dungeon_routes')->where('dungeon_id', '<', 28)->update(['published' => 0]);
        } catch (Exception $ex) {
            logger()->warning('Unable to find published column - this is probably OK');
        }
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
