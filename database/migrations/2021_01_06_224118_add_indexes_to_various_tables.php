<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToVariousTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_groups', function (Blueprint $table)
        {
            $table->index('season_id');
        });

        Schema::table('brushlines', function (Blueprint $table)
        {
            $table->index('floor_id');
        });

        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->index('published_state_id');
            $table->index('expires_at');
        });

        Schema::table('enemy_active_auras', function (Blueprint $table)
        {
            $table->index('enemy_id');
            $table->index('spell_id');
        });

        Schema::table('files', function (Blueprint $table)
        {
            $table->index(['model_id', 'model_class']);
        });

        Schema::table('kill_zone_enemies', function (Blueprint $table)
        {
            $table->index('enemy_id');
            $table->index(['kill_zone_id', 'enemy_id']);
        });

        Schema::table('map_icons', function (Blueprint $table)
        {
            $table->index('map_icon_type_id');
        });

        Schema::table('map_object_to_awakened_obelisk_links', function (Blueprint $table)
        {
            $table->index('source_map_object_id', 'motaol_source_map_object_id_index');
            $table->index(['source_map_object_id', 'source_map_object_class_name'], 'motaol_source_map_object_id_source_map_object_class_name_index');
        });

        Schema::table('mapping_change_logs', function (Blueprint $table)
        {
            $table->index('model_id');
            $table->index(['model_id', 'model_class']);
        });

        Schema::table('mdt_imports', function (Blueprint $table)
        {
            $table->index('dungeon_route_id');
        });

        Schema::table('npc_spells', function (Blueprint $table)
        {
            $table->index('npc_id');
            $table->index('spell_id');
        });

        Schema::table('npcs', function (Blueprint $table)
        {
            $table->index('npc_type_id');
            $table->index('npc_class_id');
        });

        Schema::table('oauth_tokens', function (Blueprint $table)
        {
            $table->index('user_id');
        });

        Schema::table('polylines', function (Blueprint $table)
        {
            $table->index('model_id');
            $table->index(['model_id', 'model_class']);
        });

        Schema::table('prideful_enemies', function (Blueprint $table)
        {
            $table->index('dungeon_route_id');
            $table->index('enemy_id');
            $table->index('floor_id');
        });

        Schema::table('release_changelog_changes', function (Blueprint $table)
        {
            $table->index('release_changelog_id');
            $table->index('release_changelog_category_id');
        });

        Schema::table('release_changelogs', function (Blueprint $table)
        {
            $table->index('release_id');
        });

        Schema::table('release_report_logs', function (Blueprint $table)
        {
            $table->index('release_id');
        });

        Schema::table('releases', function (Blueprint $table)
        {
            $table->index('release_changelog_id');
        });

        Schema::table('seasons', function (Blueprint $table)
        {
            $table->index('seasonal_affix_id');
        });

        Schema::table('team_users', function (Blueprint $table)
        {
            $table->index('team_id');
            $table->index('user_id');
        });

        Schema::table('user_reports', function (Blueprint $table)
        {
            $table->index(['model_id', 'model_class']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::table('user_reports', function (Blueprint $table)
//        {
//            $table->dropIndex(['model_id', 'model_class']);
//            $table->dropIndex(['user_id']);
//        });
//
//        Schema::table('team_users', function (Blueprint $table)
//        {
//            $table->dropIndex(['team_id']);
//            $table->dropIndex(['user_id']);
//        });
//
//        Schema::table('seasons', function (Blueprint $table)
//        {
//            $table->dropIndex(['seasonal_affix_id']);
//        });
//
//        Schema::table('releases', function (Blueprint $table)
//        {
//            $table->dropIndex(['release_changelog_id']);
//        });
//
//        Schema::table('release_report_logs', function (Blueprint $table)
//        {
//            $table->dropIndex(['release_id']);
//        });
//
//        Schema::table('release_changelogs', function (Blueprint $table)
//        {
//            $table->dropIndex(['release_id']);
//        });
//
//        Schema::table('release_changelog_changes', function (Blueprint $table)
//        {
//            $table->dropIndex(['release_changelog_id']);
//            $table->dropIndex(['release_changelog_category_id']);
//        });
//
//        Schema::table('prideful_enemies', function (Blueprint $table)
//        {
//            $table->dropIndex(['dungeon_route_id']);
//            $table->dropIndex(['enemy_id']);
//            $table->dropIndex(['floor_id']);
//        });
//
//        Schema::table('polylines', function (Blueprint $table)
//        {
//            $table->dropIndex(['model_id']);
//            $table->dropIndex(['model_id', 'model_class']);
//        });
//
//        Schema::table('oauth_tokens', function (Blueprint $table)
//        {
//            $table->dropIndex(['user_id']);
//        });
//
//        Schema::table('npcs', function (Blueprint $table)
//        {
//            $table->dropIndex(['npc_type_id']);
//            $table->dropIndex(['npc_class_id']);
//        });
//
//        Schema::table('npc_spells', function (Blueprint $table)
//        {
//            $table->dropIndex(['npc_id']);
//            $table->dropIndex(['spell_id']);
//        });
//
//        Schema::table('mdt_imports', function (Blueprint $table)
//        {
//            $table->dropIndex(['dungeon_route_id']);
//        });
//
//        Schema::table('mapping_change_logs', function (Blueprint $table)
//        {
//            $table->dropIndex(['model_id']);
//            $table->dropIndex(['model_id', 'model_class']);
//        });

        Schema::table('map_object_to_awakened_obelisk_links', function (Blueprint $table)
        {
            $table->dropIndex('motaol_source_map_object_id_index');
            $table->dropIndex('motaol_source_map_object_id_source_map_object_class_name_index');
        });

        Schema::table('map_icons', function (Blueprint $table)
        {
            $table->dropIndex(['map_icon_type_id']);
        });

        Schema::table('kill_zone_enemies', function (Blueprint $table)
        {
            $table->dropIndex(['enemy_id']);
            $table->dropIndex(['kill_zone_id', 'enemy_id']);
        });

        Schema::table('files', function (Blueprint $table)
        {
            $table->dropIndex(['model_id', 'model_class']);
        });

        Schema::table('enemy_active_auras', function (Blueprint $table)
        {
            $table->dropIndex(['enemy_id']);
            $table->dropIndex(['spell_id']);
        });

        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['published_state_id']);
        });

        Schema::table('brushlines', function (Blueprint $table)
        {
            $table->dropIndex(['floor_id']);
        });

        Schema::table('affix_groups', function (Blueprint $table)
        {
            $table->dropIndex(['season_id']);
        });
    }
}
