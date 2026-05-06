<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Step 1: Insert missing npc_dungeons records based on enemies -> mapping_versions -> dungeon_id
        DB::statement("
            INSERT IGNORE INTO npc_dungeons (npc_id, dungeon_id)
            SELECT DISTINCT e.npc_id, mv.dungeon_id
            FROM enemies e
            INNER JOIN mapping_versions mv ON e.mapping_version_id = mv.id
            WHERE e.npc_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM npc_dungeons nd
                  WHERE nd.npc_id = e.npc_id
                    AND nd.dungeon_id = mv.dungeon_id
              )
        ");
    }

    public function down(): void
    {
        // Optional: Delete the inserted ones. But ONLY those that were inferred from mapping_versions.
        DB::statement("
            DELETE nd FROM npc_dungeons nd
            INNER JOIN enemies e ON nd.npc_id = e.npc_id
            INNER JOIN mapping_versions mv ON e.mapping_version_id = mv.id
            WHERE nd.dungeon_id = mv.dungeon_id
        ");
    }
};
