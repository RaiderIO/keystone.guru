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
        // Non-atomic check-then-insert writers let concurrent combat log ingestion create more
        // than one row for the same (spell_id, dungeon_id) pair - keep the oldest row per pair
        // and drop the rest before the unique index below can be created.
        DB::delete('
            DELETE sd FROM spell_dungeons sd
            INNER JOIN (
                SELECT spell_id, dungeon_id, MIN(id) AS keep_id
                FROM spell_dungeons
                GROUP BY spell_id, dungeon_id
            ) keep ON keep.spell_id = sd.spell_id AND keep.dungeon_id = sd.dungeon_id
            WHERE sd.id > keep.keep_id
        ');

        Schema::table('spell_dungeons', function (Blueprint $table) {
            $table->unique(['spell_id', 'dungeon_id'], 'spell_dungeons_spell_id_dungeon_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spell_dungeons', function (Blueprint $table) {
            $table->dropUnique('spell_dungeons_spell_id_dungeon_id_unique');
        });
    }
};
