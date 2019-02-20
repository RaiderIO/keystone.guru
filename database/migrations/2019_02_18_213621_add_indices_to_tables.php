<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndicesToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->index('polyline_id');
        });
        Schema::table('kill_zones', function (Blueprint $table) {
            $table->index(['dungeon_route_id', 'floor_id']);
        });
        // Reversed is intentional
        Schema::table('map_comments', function (Blueprint $table) {
            $table->index(['floor_id', 'dungeon_route_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->dropIndex('polyline_id');
        });
        Schema::table('kill_zones', function (Blueprint $table) {
            $table->dropIndex(['dungeon_route_id', 'floor_id']);
        });
        // Reversed is intentional
        Schema::table('map_comments', function (Blueprint $table) {
            $table->dropIndex(['floor_id', 'dungeon_route_id']);
        });
    }
}
