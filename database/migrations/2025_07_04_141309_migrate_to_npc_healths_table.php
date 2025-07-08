<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            INSERT INTO npc_healths (npc_id, game_version_id, health, percentage)
            SELECT DISTINCT
                npcs.id AS npc_id,
                dungeons.game_version_id,
                npcs.base_health AS health,
                npcs.health_percentage AS percentage
            FROM npcs
            INNER JOIN npc_dungeons ON npc_dungeons.npc_id = npcs.id
            INNER JOIN dungeons ON npc_dungeons.dungeon_id = dungeons.id
            WHERE npcs.base_health IS NOT NULL
              AND dungeons.game_version_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Delete only the records we inserted based on this logic
        DB::statement("
            DELETE nh FROM npc_healths nh
            INNER JOIN npcs ON nh.npc_id = npcs.id
        ");
    }
};
