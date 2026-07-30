<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const DUNGEON_KEYS = ['cathedralofeternalnight', 'mawofsouls'];

    private const LEGION_REMIX_GAME_VERSION_KEY = 'legion-remix';

    /**
     * Cathedral of Eternal Night and Maw of Souls lose their retail mapping version in this release: both
     * were empty stubs (one held a single map icon, the other nothing at all) that claimed facade_enabled
     * without any floor unions, which makes MDTDungeon::getClonesAsEnemies() throw
     * FacadeNotConfiguredException on regular player actions - exporting a route as an MDT string, or
     * importing pulls/raid markers into one. All of both dungeons' real mapping data lives on their Legion
     * Remix mapping versions.
     *
     * Removing them from the seeder leaves every existing route on those dungeons pointing at a
     * mapping_version_id that no longer exists, and DungeonRoute reads its mapping version without a null
     * check (getEnemyForcesPercentage() among others), so those routes would 500 instead of rendering.
     * There were 109 of them in the production snapshot - all published, none with a single pull, which
     * follows from those mapping versions never having had any enemies to pull.
     *
     * This repoints them onto the dungeon's current Legion Remix mapping version, which does have the
     * mapping. Selecting by "not one of the surviving mapping versions" rather than by the removed ids
     * makes this correct whether it runs before or after the seeder drops them.
     *
     * The repointed routes change game version along with their mapping version, so they move from the
     * retail listings into the Legion Remix ones and keep a `season_id` naming a retail season. Their
     * thumbnails are also left as they are. All of that is cosmetic here precisely because these routes have
     * no pulls - there is no content whose meaning could shift - and it beats the alternative of a 500.
     */
    public function up(): void
    {
        $legionRemixGameVersionId = DB::table('game_versions')
            ->where('key', self::LEGION_REMIX_GAME_VERSION_KEY)
            ->value('id');

        if ($legionRemixGameVersionId === null) {
            return;
        }

        foreach (self::DUNGEON_KEYS as $dungeonKey) {
            $dungeonId = DB::table('dungeons')->where('key', $dungeonKey)->value('id');

            if ($dungeonId === null) {
                continue;
            }

            // Scoped by game version - `version` is only unique per game_version_id (#3720)
            $survivingMappingVersionIds = DB::table('mapping_versions')
                ->where('dungeon_id', $dungeonId)
                ->where('game_version_id', $legionRemixGameVersionId)
                ->orderByDesc('version')
                ->pluck('id');

            if ($survivingMappingVersionIds->isEmpty()) {
                continue;
            }

            // whereNotIn evaluates to NULL for a NULL mapping_version_id, so those rows need saying out loud -
            // they read their mapping version just as unguardedly as a dangling one does.
            DB::table('dungeon_routes')
                ->where('dungeon_id', $dungeonId)
                ->where(static function ($query) use ($survivingMappingVersionIds) {
                    $query->whereNotIn('mapping_version_id', $survivingMappingVersionIds)
                        ->orWhereNull('mapping_version_id');
                })
                ->update(['mapping_version_id' => $survivingMappingVersionIds->first()]);
        }
    }

    /**
     * Not reversible: the mapping versions these routes used to point at are removed from the seeder, so
     * there is nothing to point them back at.
     */
    public function down(): void
    {
    }
};
