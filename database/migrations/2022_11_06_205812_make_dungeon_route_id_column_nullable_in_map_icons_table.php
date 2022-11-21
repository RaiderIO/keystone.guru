<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeDungeonRouteIdColumnNullableInMapIconsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->integer('dungeon_route_id')->nullable()->default(null)->change();
        });

        DB::update('UPDATE `map_icons` SET `dungeon_route_id` = null WHERE `dungeon_route_id` <= 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->integer('dungeon_route_id')->nullable(false)->default(-1)->change();
        });
    }
}
