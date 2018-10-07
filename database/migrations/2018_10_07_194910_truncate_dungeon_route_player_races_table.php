<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TruncateDungeonRoutePlayerRacesTable extends Migration
{    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Gotta truncate it since kul'tiran and zandalari trolls got removed due to their class/race combinations being unknown.
        // Website is not live yet, so I can clear it rather than spend hours converting.
        DB::table('dungeon_route_player_races')->truncate();
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
