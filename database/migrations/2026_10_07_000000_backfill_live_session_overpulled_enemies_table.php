<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Copies the overpulled enemies of running live sessions into the table that replaces `overpulled_enemies`.
     * Idempotent: a row whose (live_session_id, npc_id, mdt_id) is already present is skipped.
     */
    public function up(): void
    {
        DB::statement('
            INSERT INTO `live_session_overpulled_enemies` (`live_session_id`, `kill_zone_id`, `npc_id`, `mdt_id`)
            SELECT `oe`.`live_session_id`, `oe`.`kill_zone_id`, `oe`.`npc_id`, `oe`.`mdt_id`
            FROM `overpulled_enemies` `oe`
            WHERE NOT EXISTS (
                SELECT 1
                FROM `live_session_overpulled_enemies` `lsoe`
                WHERE `lsoe`.`live_session_id` = `oe`.`live_session_id`
                  AND `lsoe`.`npc_id` <=> `oe`.`npc_id`
                  AND `lsoe`.`mdt_id` <=> `oe`.`mdt_id`
            )
        ');
    }

    public function down(): void
    {
        // The copied rows are indistinguishable from rows written since; `overpulled_enemies` still holds the originals.
    }
};
