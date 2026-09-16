<?php

use App\Models\DungeonRoute\DungeonRoute;
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
            $table->timestamp('last_accessed_at')->nullable()->after('thumbnail_updated_at');
        });

        // page_views only retains the last keystoneguru.page_views.retention_days, so routes not viewed within
        // that window stay null
        DB::statement(<<<'SQL'
            UPDATE dungeon_routes dr
            INNER JOIN (
                SELECT model_id, MAX(created_at) AS last_view
                FROM page_views
                WHERE model_class = ?
                GROUP BY model_id
            ) pv ON pv.model_id = dr.id
            SET dr.last_accessed_at = pv.last_view
            SQL, [DungeonRoute::class]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropColumn('last_accessed_at');
        });
    }
};
