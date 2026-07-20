<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeon_route_enemy_raid_markers', function (Blueprint $table) {
            $table->integer('mdt_id')->default(null)->nullable()->after('raid_marker_id');
            $table->integer('npc_id')->default(null)->nullable()->after('raid_marker_id');

            $table->index(['npc_id', 'mdt_id']);

            // enemy_id becomes a cached/derived pointer resolved from npc_id+mdt_id on every
            // mapping version upgrade, mirroring kill_zone_enemies (#1453) - restate its full
            // prior definition per the Laravel 12 migration rule.
            $table->integer('enemy_id')->nullable()->change();
        });

        // Table is tiny (this feature has been broken/disabled since 2022) - safe to backfill
        // inline rather than via a chunked command, mirroring the original (pre-#3246-rework)
        // kill_zone_enemies conversion migration.
        DB::update('
            UPDATE `dungeon_route_enemy_raid_markers`
                LEFT JOIN `enemies` ON `enemies`.`id` = `dungeon_route_enemy_raid_markers`.`enemy_id`
            SET `dungeon_route_enemy_raid_markers`.`npc_id` = coalesce(`enemies`.`mdt_npc_id`, `enemies`.`npc_id`),
                `dungeon_route_enemy_raid_markers`.`mdt_id` = `enemies`.`mdt_id`
                WHERE `enemies`.`mdt_id` is not null;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_route_enemy_raid_markers', function (Blueprint $table) {
            $table->integer('enemy_id')->nullable(false)->change();

            $table->dropIndex(['npc_id', 'mdt_id']);

            $table->dropColumn('mdt_id');
            $table->dropColumn('npc_id');
        });
    }
};
