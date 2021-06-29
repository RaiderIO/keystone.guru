<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDungeonRouteLatLngIndexOnKillZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kill_zones', function (Blueprint $table)
        {
            $table->index(['dungeon_route_id', 'lat', 'lng']);
        });
    }

    /**
     * Reverse the migrations.
     *w
     * @return void
     */
    public function down()
    {
        Schema::table('kill_zones', function (Blueprint $table)
        {
            $table->dropIndex(['dungeon_route_id', 'lat', 'lng']);
        });
    }
}
