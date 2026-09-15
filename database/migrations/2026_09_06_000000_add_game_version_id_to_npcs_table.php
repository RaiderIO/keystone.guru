<?php

use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('npcs', static function (Blueprint $table): void {
            $table->unsignedInteger('game_version_id')
                ->default(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
                ->after('id');

            $table->index('game_version_id');
        });

        // Existing rows predate the column, so derive their game version from the dungeons they are
        // assigned to. An NPC that appears in dungeons of several game versions (a retail dungeon
        // that also has a Classic mapping, for example) keeps retail when retail is one of them, and
        // otherwise takes the lowest matching game version - Wotuu can correct those in the admin.
        DB::statement(sprintf(<<<'SQL'
            UPDATE npcs n
            INNER JOIN (
                SELECT nd.npc_id,
                       COALESCE(
                           MAX(CASE WHEN mv.game_version_id = %1$d THEN %1$d END),
                           MIN(mv.game_version_id)
                       ) AS game_version_id
                FROM npc_dungeons nd
                INNER JOIN mapping_versions mv ON mv.dungeon_id = nd.dungeon_id
                GROUP BY nd.npc_id
            ) derived ON derived.npc_id = n.id
            SET n.game_version_id = derived.game_version_id
            SQL, GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]));
    }

    public function down(): void
    {
        Schema::table('npcs', static function (Blueprint $table): void {
            $table->dropIndex(['game_version_id']);
            $table->dropColumn('game_version_id');
        });
    }
};
