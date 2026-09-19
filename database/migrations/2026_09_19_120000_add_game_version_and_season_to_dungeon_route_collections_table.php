<?php

use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_route_collections', static function (Blueprint $table): void {
            $table->unsignedInteger('game_version_id')->nullable()->after('dungeon_route_collection_category_id');
            $table->unsignedInteger('season_id')->nullable()->after('game_version_id');

            $table->index('game_version_id');
            $table->index('season_id');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('dungeon_route_collections', static function (Blueprint $table): void {
            $table->dropIndex(['season_id']);
            $table->dropIndex(['game_version_id']);
            $table->dropColumn(['season_id', 'game_version_id']);
        });
    }

    /**
     * Gives every collection without a game version the one most of its routes have through their own mapping
     * version (ties go to retail, then the lowest id), an empty collection its owner's current game version, and
     * whatever is left retail. The season stays null: every existing collection becomes free-form.
     */
    public function backfill(): void
    {
        $retailId = GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL];

        DB::statement(sprintf(<<<'SQL'
            UPDATE dungeon_route_collections c
            SET c.game_version_id = (
                SELECT mv.game_version_id
                FROM dungeon_route_collection_routes cr
                INNER JOIN dungeon_routes dr ON dr.id = cr.dungeon_route_id
                INNER JOIN mapping_versions mv ON mv.id = dr.mapping_version_id
                WHERE cr.dungeon_route_collection_id = c.id
                GROUP BY mv.game_version_id
                ORDER BY COUNT(*) DESC, mv.game_version_id = %1$d DESC, mv.game_version_id ASC
                LIMIT 1
            )
            WHERE c.game_version_id IS NULL
            SQL, $retailId));

        DB::statement(<<<'SQL'
            UPDATE dungeon_route_collections c
            INNER JOIN users u ON u.id = c.user_id
            INNER JOIN game_versions gv ON gv.id = u.game_version_id
            SET c.game_version_id = gv.id
            WHERE c.game_version_id IS NULL
            SQL);

        DB::table('dungeon_route_collections')
            ->whereNull('game_version_id')
            ->update(['game_version_id' => $retailId]);
    }
};
