<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropIndex(['mapping_version_id']);
        });

        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->dropIndex(['floor_id', 'mapping_version_id']);

            $table->index(['floor_id']);
            $table->index(['mapping_version_id', 'floor_id']);
        });

        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->dropIndex(['floor_id', 'mapping_version_id']);

            $table->index(['floor_id']);
            $table->index(['mapping_version_id', 'floor_id']);
        });

        Schema::table('floor_union_areas', function (Blueprint $table) {
            $table->dropIndex(['mapping_version_id']);

            $table->index(['mapping_version_id', 'floor_id']);
        });

        Schema::table('floor_unions', function (Blueprint $table) {
            $table->dropIndex(['mapping_version_id']);

            $table->index(['mapping_version_id', 'floor_id']);
        });

        Schema::table('kill_zones', function (Blueprint $table) {
            $table->dropIndex(['dungeon_route_id', 'lat', 'lng']);
        });

        Schema::table('map_icons', function (Blueprint $table) {
            $table->dropIndex('map_icons_floor_route_version_index');

            $table->index(['floor_id']);
            $table->index(['mapping_version_id', 'floor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->index(['mapping_version_id']);
        });

        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->index(['floor_id', 'mapping_version']);

            $table->dropIndex(['floor_id']);
            $table->dropIndex(['mapping_version_id', 'floor_id']);
        });

        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->index(['floor_id', 'mapping_version']);

            $table->dropIndex(['floor_id']);
            $table->dropIndex(['mapping_version_id', 'floor_id']);
        });

        Schema::table('floor_union_areas', function (Blueprint $table) {
            $table->index(['mapping_version']);
            $table->dropIndex(['mapping_version_id', 'floor_id']);
        });

        Schema::table('floor_unions', function (Blueprint $table) {
            $table->index(['mapping_version']);
            $table->dropIndex(['mapping_version_id', 'floor_id']);
        });

        Schema::table('kill_zones', function (Blueprint $table) {
            $table->index(['dungeon_route_id', 'lat', 'lng']);
        });

        Schema::table('map_icons', function (Blueprint $table) {
            $table->index(['floor_id', 'dungeon_route_id', 'mapping_version_id']);

            $table->dropIndex(['floor_id']);
            $table->dropIndex(['mapping_version_id', 'floor_id']);
        });
    }
};
