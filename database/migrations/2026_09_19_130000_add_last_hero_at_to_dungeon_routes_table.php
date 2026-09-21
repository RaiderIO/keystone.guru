<?php

use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
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
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->timestamp('last_hero_at')->nullable()->after('last_accessed_at');
        });

        // Routes that already hold hero or front page thumbnails get a full grace period before those are
        // expired, instead of losing them on the first run after this deploys
        DB::statement(<<<'SQL'
            UPDATE dungeon_routes
            SET last_hero_at = NOW()
            WHERE id IN (
                SELECT DISTINCT dungeon_route_id
                FROM dungeon_route_thumbnails
                WHERE variant IN (?, ?)
            )
            SQL, [DungeonRouteThumbnailVariant::Hero->value, DungeonRouteThumbnailVariant::FrontPage->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropColumn('last_hero_at');
        });
    }
};
