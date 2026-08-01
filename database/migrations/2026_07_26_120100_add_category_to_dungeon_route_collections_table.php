<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeon_route_collections', function (Blueprint $table) {
            // Optional: a collection without a category is perfectly valid, and every collection
            // that existed before this migration has none
            $table->unsignedInteger('dungeon_route_collection_category_id')
                ->nullable()
                ->after('team_id')
                ->index('drc_collections_category_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_route_collections', function (Blueprint $table) {
            $table->dropIndex('drc_collections_category_id_index');
            $table->dropColumn('dungeon_route_collection_category_id');
        });
    }
};
