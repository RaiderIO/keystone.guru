<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affix_group_couplings', function (Blueprint $table) {
            $table->index('affix_id');
            $table->index('affix_group_id');
        });
        Schema::table('affixes', function (Blueprint $table) {
            $table->index('icon_file_id');
        });
        Schema::table('character_class_specializations', function (Blueprint $table) {
            $table->index('character_class_id');
            $table->index('icon_file_id');
        });
        Schema::table('character_classes', function (Blueprint $table) {
            $table->index('icon_file_id');
        });
        Schema::table('character_race_class_couplings', function (Blueprint $table) {
            $table->index('character_race_id');
            $table->index('character_class_id');
        });
        Schema::table('character_races', function (Blueprint $table) {
            $table->index('faction_id');
        });
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->index('floor_id');
            $table->index('target_floor_id');
        });
        Schema::table('dungeon_route_affix_groups', function (Blueprint $table) {
            $table->index('dungeon_route_id');
            $table->index('affix_group_id');
        });
        Schema::table('dungeon_route_enemy_raid_markers', function (Blueprint $table) {
            $table->index('dungeon_route_id');
            $table->index('enemy_id');
            $table->index('raid_marker_id');
        });
        Schema::table('dungeon_route_favorites', function (Blueprint $table) {
            $table->index('dungeon_route_id');
            $table->index('user_id');
        });
        Schema::table('dungeon_route_player_classes', function (Blueprint $table) {
            $table->index('dungeon_route_id');
            $table->index('character_class_id');
        });
        Schema::table('dungeon_route_player_races', function (Blueprint $table) {
            $table->index('dungeon_route_id');
            $table->index('character_race_id');
        });
        Schema::table('dungeon_route_player_specializations', function (Blueprint $table) {
            $table->index('dungeon_route_id');
            // Have to rename it due to length constraints
            $table->index('character_class_specialization_id', 'dr_player_spec_char_class_spec_id_index');
        });
        Schema::table('dungeon_route_ratings', function (Blueprint $table) {
            $table->index('dungeon_route_id');
            $table->index('user_id');
        });
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->index('author_id');
            $table->index('dungeon_id');
            $table->index('faction_id');
        });
        Schema::table('dungeon_start_markers', function (Blueprint $table) {
            $table->index('floor_id');
        });
        Schema::table('dungeons', function (Blueprint $table) {
            $table->index('expansion_id');
        });
        Schema::table('enemies', function (Blueprint $table) {
            $table->index('enemy_pack_id');
            $table->index('npc_id');
            $table->index('floor_id');
        });
        Schema::table('enemy_pack_vertices', function (Blueprint $table) {
            $table->index('enemy_pack_id');
        });
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->index('floor_id');
        });
        Schema::table('enemy_patrol_vertices', function (Blueprint $table) {
            $table->index('enemy_patrol_id');
        });
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->index('floor_id');
            $table->index('enemy_id');
        });
        Schema::table('expansions', function (Blueprint $table) {
            $table->index('icon_file_id');
        });
        Schema::table('factions', function (Blueprint $table) {
            $table->index('icon_file_id');
        });
        Schema::table('files', function (Blueprint $table) {
            $table->index('model_id');
        });
        Schema::table('floor_couplings', function (Blueprint $table) {
            $table->index('floor1_id');
            $table->index('floor2_id');
        });
        Schema::table('floors', function (Blueprint $table) {
            $table->index('dungeon_id');
        });
        Schema::table('game_icon', function (Blueprint $table) {
            $table->index('file_id');
        });
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->index('kill_zone_id');
        });
        \Illuminate\Support\Facades\Schema::table('kill_zones', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('dungeon_route_id');
            $table->index('floor_id');
        });
        \Illuminate\Support\Facades\Schema::table('map_comments', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('floor_id');
            $table->index('dungeon_route_id');
            $table->index('game_icon_id');
        });
        \Illuminate\Support\Facades\Schema::table('npcs', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('dungeon_id');
            $table->index('classification_id');
        });
        \Illuminate\Support\Facades\Schema::table('patreon_data', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('user_id');
        });
        \Illuminate\Support\Facades\Schema::table('patreon_tiers', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('patreon_data_id');
            $table->index('paid_tier_id');
        });
        \Illuminate\Support\Facades\Schema::table('route_vertices', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('route_id');
        });
        \Illuminate\Support\Facades\Schema::table('routes', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('dungeon_route_id');
            $table->index('floor_id');
        });
        \Illuminate\Support\Facades\Schema::table('user_reports', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('author_id');
        });
        \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->index('patreon_data_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affix_group_couplings', function (Blueprint $table) {
            $table->dropIndex('affix_id');
            $table->dropIndex('affix_group_id');
        });
        Schema::table('affixes', function (Blueprint $table) {
            $table->dropIndex('icon_file_id');
        });
        Schema::table('character_class_specializations', function (Blueprint $table) {
            $table->dropIndex('character_class_id');
            $table->dropIndex('icon_file_id');
        });
        Schema::table('character_classes', function (Blueprint $table) {
            $table->dropIndex('icon_file_id');
        });
        Schema::table('character_race_class_couplings', function (Blueprint $table) {
            $table->dropIndex('character_race_id');
            $table->dropIndex('character_class_id');
        });
        Schema::table('character_races', function (Blueprint $table) {
            $table->dropIndex('faction_id');
        });
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->dropIndex('floor_id');
            $table->dropIndex('target_floor_id');
        });
        Schema::table('dungeon_route_affix_groups', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('affix_group_id');
        });
        Schema::table('dungeon_route_enemy_raid_markers', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('enemy_id');
            $table->dropIndex('raid_marker_id');
        });
        Schema::table('dungeon_route_favorites', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('user_id');
        });
        Schema::table('dungeon_route_player_classes', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('character_class_id');
        });
        Schema::table('dungeon_route_player_races', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('character_race_id');
        });
        Schema::table('dungeon_route_player_specializations', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('character_class_specialization_id');
        });
        Schema::table('dungeon_route_ratings', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('user_id');
        });
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropIndex('author_id');
            $table->dropIndex('dungeon_id');
            $table->dropIndex('faction_id');
        });
        Schema::table('dungeon_start_markers', function (Blueprint $table) {
            $table->dropIndex('floor_id');
        });
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropIndex('expansion_id');
        });
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropIndex('enemy_pack_id');
            $table->dropIndex('npc_id');
            $table->dropIndex('floor_id');
        });
        Schema::table('enemy_pack_vertices', function (Blueprint $table) {
            $table->dropIndex('enemy_pack_id');
        });
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->dropIndex('floor_id');
        });
        Schema::table('enemy_patrol_vertices', function (Blueprint $table) {
            $table->dropIndex('enemy_patrol_id');
        });
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->dropIndex('floor_id');
            $table->dropIndex('enemy_id');
        });
        Schema::table('expansions', function (Blueprint $table) {
            $table->dropIndex('icon_file_id');
        });
        Schema::table('factions', function (Blueprint $table) {
            $table->dropIndex('icon_file_id');
        });
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex('model_id');
        });
        Schema::table('floor_couplings', function (Blueprint $table) {
            $table->dropIndex('floor1_id');
            $table->dropIndex('floor2_id');
        });
        Schema::table('floors', function (Blueprint $table) {
            $table->dropIndex('dungeon_id');
        });
        Schema::table('game_icon', function (Blueprint $table) {
            $table->dropIndex('file_id');
        });
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->dropIndex('kill_zone_id');
        });
        Schema::table('kill_zones', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('floor_id');
        });
        Schema::table('map_comments', function (Blueprint $table) {
            $table->dropIndex('floor_id');
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('game_icon_id');
        });
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropIndex('dungeon_id');
            $table->dropIndex('classification_id');
        });
        Schema::table('patreon_data', function (Blueprint $table) {
            $table->dropIndex('user_id');
        });
        Schema::table('patreon_tiers', function (Blueprint $table) {
            $table->dropIndex('patreon_data_id');
            $table->dropIndex('paid_tier_id');
        });
        Schema::table('route_vertices', function (Blueprint $table) {
            $table->dropIndex('route_id');
        });
        Schema::table('routes', function (Blueprint $table) {
            $table->dropIndex('dungeon_route_id');
            $table->dropIndex('floor_id');
        });
        Schema::table('user_reports', function (Blueprint $table) {
            $table->dropIndex('author_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('patreon_data_id');
        });
    }
}
