<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Backfill the `variant` column for pre-existing custom thumbnails so the variant becomes the single
     * source of truth (a thumbnail is 'standard', 'hero' or 'custom'). The legacy `custom` boolean is kept
     * and dual-written by the application until a follow-up migration drops it - see the expand/contract note
     * on DungeonRouteThumbnail (#3447).
     */
    public function up(): void
    {
        DB::table('dungeon_route_thumbnails')
            ->where('custom', true)
            ->update(['variant' => 'custom']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('dungeon_route_thumbnails')
            ->where('variant', 'custom')
            ->update(['variant' => 'standard']);
    }
};
