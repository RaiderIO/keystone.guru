<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamIdToMapIconsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->integer('team_id')->nullable(true)->after('dungeon_route_id');

            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->removeColumn('team_id');

            $table->dropIndex('team_id');
        });
    }
}
