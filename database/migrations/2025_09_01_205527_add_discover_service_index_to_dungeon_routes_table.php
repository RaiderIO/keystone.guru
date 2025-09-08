<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('dungeon_routes', function (Blueprint $table) {
                // Manually added to alleviate immediate performance issues
                $table->dropIndex('dr_dungeon_pub_exp');
            });
        } catch (QueryException $exception) {
            // We don't care if this fails, the index doesn't exist
        }

        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->index([
                'dungeon_id',
                'published_state_id',
                'expires_at'
            ], 'dungeon_routes_dungeon_pub_exp');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropIndex('dungeon_routes_dungeon_pub_exp');
        });
    }
};
