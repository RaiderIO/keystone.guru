<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameGameIconIdToIconTypeColumnMapIconsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->dropColumn('game_icon_id');
            $table->string('icon_type')->after('dungeon_route_id');
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
            $table->dropColumn('icon_type');
            $table->integer('game_icon_id')->after('dungeon_route_id');
        });
    }
}
