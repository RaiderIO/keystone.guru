<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const PUG_FRIENDLY_CATEGORY_ID = 1;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('dungeon_route_collections')
            ->where('dungeon_route_collection_category_id', self::PUG_FRIENDLY_CATEGORY_ID)
            ->update(['dungeon_route_collection_category_id' => null]);

        DB::table('dungeon_route_collection_categories')
            ->where('id', self::PUG_FRIENDLY_CATEGORY_ID)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The collections that were cleared are indistinguishable from collections that never had a category
        DB::table('dungeon_route_collection_categories')->insertOrIgnore([
            'id'   => self::PUG_FRIENDLY_CATEGORY_ID,
            'name' => 'pug_friendly',
        ]);
    }
};
