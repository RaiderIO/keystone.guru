<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Season of Discovery Ruins/Temple of Ahn'Qiraj dungeon id => the Classic dungeon id it is merged into.
     * The main database's migration of the same name does the merge itself. This database cannot read
     * the dungeons, so the ids are spelled out; they are seeded and identical in every environment.
     *
     * @var array<int, int>
     */
    private const array DUNGEON_ID_MAP = [
        125 => 123,
        126 => 124,
    ];

    /** @var array<int, int> Season of Discovery floor id => the Classic floor id at the same index. */
    private const array FLOOR_ID_MAP = [
        330 => 326,
        331 => 327,
        332 => 328,
        333 => 329,
    ];

    /** @var array<string, bool> Table => whether it also holds a floor id. */
    private const array TABLES = [
        'challenge_mode_runs'                => false,
        'combat_log_route_enemy_failures'    => true,
        'combat_log_route_enemy_resolutions' => true,
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $hasFloorId) {
            foreach (self::DUNGEON_ID_MAP as $sourceDungeonId => $targetDungeonId) {
                DB::table($table)->where('dungeon_id', $sourceDungeonId)->update(['dungeon_id' => $targetDungeonId]);
            }

            if (!$hasFloorId) {
                continue;
            }

            foreach (self::FLOOR_ID_MAP as $sourceFloorId => $targetFloorId) {
                DB::table($table)->where('floor_id', $sourceFloorId)->update(['floor_id' => $targetFloorId]);
            }
        }
    }

    /**
     * Not reversible: the rows of both dungeons are indistinguishable once merged.
     */
    public function down(): void
    {
    }
};
