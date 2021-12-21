<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdjustIndices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affixes', function (Blueprint $table)
        {
            $table->index(['key']);
        });

        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->dropIndex('dungeon_routes_published_state_id_index');
        });

        Schema::table('expansions', function (Blueprint $table)
        {
            $table->index(['shortname']);
            $table->index(['released_at']);
            $table->index(['active']);
        });

        Schema::table('factions', function (Blueprint $table)
        {
            $table->index(['key']);
        });

        Schema::table('files', function (Blueprint $table)
        {
            $table->dropIndex('files_model_id_index');
        });

        Schema::table('floors', function (Blueprint $table)
        {
            $table->dropIndex('floors_dungeon_id_index');
            $table->index(['dungeon_id', 'index']);
            $table->index(['default']);
        });

        Schema::table('game_server_regions', function (Blueprint $table)
        {
            $table->index(['short']);
        });

        Schema::table('kill_zones', function (Blueprint $table)
        {
            $table->dropIndex('kill_zones_dungeon_route_id_index');
            $table->index(['dungeon_route_id', 'index']);
        });

        Schema::table('map_icons', function (Blueprint $table)
        {
            $table->dropIndex('map_comments_floor_id_index');
        });

        Schema::table('mapping_change_logs', function (Blueprint $table)
        {
            $table->dropIndex('mapping_change_logs_model_id_index');
        });

        Schema::table('paths', function (Blueprint $table)
        {
            $table->dropIndex('routes_dungeon_route_id_index');
        });

        Schema::table('polylines', function (Blueprint $table)
        {
            $table->dropIndex('polylines_model_id_index');
        });

        Schema::table('published_states', function (Blueprint $table)
        {
            $table->index(['name']);
        });

        Schema::table('seasons', function (Blueprint $table)
        {
            $table->index(['start']);
        });

        Schema::table('tag_categories', function (Blueprint $table)
        {
            $table->index(['name']);
        });

        Schema::table('tags', function (Blueprint $table)
        {
            $table->dropIndex('tags_model_id_index');
        });

        Schema::table('team_users', function (Blueprint $table)
        {
            $table->dropIndex('team_users_team_id_index');
        });

        Schema::table('user_reports', function (Blueprint $table)
        {
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Cba
    }
}
