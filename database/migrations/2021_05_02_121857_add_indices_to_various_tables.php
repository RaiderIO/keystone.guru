<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndicesToVariousTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeons', function (Blueprint $table)
        {
            $table->index(['slug']);
            $table->index(['active']);
            $table->index(['enemy_forces_required']);
            $table->index(['enemy_forces_required_teeming']);
        });
        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->index(['thumbnail_updated_at']);
        });

        Schema::table('enemies', function (Blueprint $table)
        {
            $table->index(['seasonal_index']);
            $table->index(['mdt_id']);
            $table->index(['enemy_forces_override']);
            $table->index(['enemy_forces_override_teeming']);
        });

        Schema::table('map_icon_types', function (Blueprint $table)
        {
            $table->index(['key']);
        });

        Schema::table('map_icons', function (Blueprint $table)
        {
            $table->index(['seasonal_index']);
        });

        Schema::table('map_object_to_awakened_obelisk_links', function (Blueprint $table)
        {
            $table->index(['target_map_icon_type_id']);
            $table->index(['target_map_icon_seasonal_index']);
        });

        Schema::table('npcs', function (Blueprint $table)
        {
            $table->index(['enemy_forces']);
            $table->index(['enemy_forces_teeming']);
        });

        Schema::table('page_views', function (Blueprint $table)
        {
            $table->index(['created_at']);
        });

        Schema::table('users', function (Blueprint $table)
        {
            $table->index(['icon_file_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeons', function (Blueprint $table)
        {
            $table->dropIndex(['slug']);
            $table->dropIndex(['active']);
            $table->dropIndex(['enemy_forces_required']);
            $table->dropIndex(['enemy_forces_required_teeming']);
        });
        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->dropIndex(['thumbnail_updated_at']);
        });

        Schema::table('enemies', function (Blueprint $table)
        {
            $table->dropIndex(['seasonal_dropIndex']);
            $table->dropIndex(['mdt_id']);
            $table->dropIndex(['enemy_forces_override']);
            $table->dropIndex(['enemy_forces_override_teeming']);
        });

        Schema::table('map_icon_types', function (Blueprint $table)
        {
            $table->dropIndex(['key']);
        });

        Schema::table('map_icons', function (Blueprint $table)
        {
            $table->dropIndex(['seasonal_dropIndex']);
        });

        Schema::table('map_object_to_awakened_obelisk_links', function (Blueprint $table)
        {
            $table->dropIndex(['target_map_icon_type_id']);
            $table->dropIndex(['target_map_icon_seasonal_dropIndex']);
        });

        Schema::table('npcs', function (Blueprint $table)
        {
            $table->dropIndex(['enemy_forces']);
            $table->dropIndex(['enemy_forces_teeming']);
        });

        Schema::table('page_views', function (Blueprint $table)
        {
            $table->dropIndex(['created_at']);
        });

        Schema::table('users', function (Blueprint $table)
        {
            $table->dropIndex(['icon_file_id']);
        });
    }
}
