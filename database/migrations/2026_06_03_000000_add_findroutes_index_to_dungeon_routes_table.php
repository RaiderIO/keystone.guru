<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            // Used by DungeonRouteRepository::findRoutesBuilder(): WHERE mapping_version_id = ? AND published_state_id = ? AND clone_of IS NULL AND expires_at IS NULL ORDER BY popularity DESC LIMIT 5
            $table->index(['mapping_version_id', 'published_state_id', 'popularity'], 'dungeon_routes_findroutes_index');
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropIndex('dungeon_routes_findroutes_index');
        });
    }
};
