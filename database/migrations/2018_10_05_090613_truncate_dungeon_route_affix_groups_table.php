<?php

use Illuminate\Database\Migrations\Migration;

class TruncateDungeonRouteAffixGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Gotta truncate it since affix groups got updated. Website is not live yet, so I can clear it rather than spend hours converting.
        DB::table('dungeon_route_affix_groups')->truncate();
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
