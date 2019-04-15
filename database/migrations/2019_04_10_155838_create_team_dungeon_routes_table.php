<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_dungeon_routes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id');
            $table->integer('dungeon_route_id');
            $table->timestamps();

            $table->index(['team_id', 'dungeon_route_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_dungeon_routes');
    }
}
