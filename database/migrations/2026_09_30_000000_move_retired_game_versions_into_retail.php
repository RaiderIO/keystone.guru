<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const array RETIRED_GAME_VERSION_KEYS = ['wotlk', 'cata', 'legion-remix'];

    private const string RETAIL_GAME_VERSION_KEY = 'retail';

    /**
     * Moves the user-owned rows that name a retired game version onto Retail. The seeded rows (game versions,
     * mapping versions, NPCs and their healths) are moved by the seeder instead, and routes follow their
     * mapping version.
     */
    public function up(): void
    {
        $retailGameVersionId = DB::table('game_versions')
            ->where('key', self::RETAIL_GAME_VERSION_KEY)
            ->value('id');

        $retiredGameVersionIds = DB::table('game_versions')
            ->whereIn('key', self::RETIRED_GAME_VERSION_KEYS)
            ->pluck('id');

        if ($retailGameVersionId === null || $retiredGameVersionIds->isEmpty()) {
            return;
        }

        foreach (['users', 'dungeon_route_collections'] as $table) {
            DB::table($table)
                ->whereIn('game_version_id', $retiredGameVersionIds)
                ->update(['game_version_id' => $retailGameVersionId]);
        }
    }

    /**
     * Not reversible: which rows were on which retired game version is not kept.
     */
    public function down(): void
    {
    }
};
