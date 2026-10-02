<?php

use App\Models\Dungeon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** @var array<string, string> Source dungeon key => the dungeon key it is merged into. */
    private const array MERGES = [
        'ruins_of_ahnqiraj_sod'  => 'ruins_of_ahnqiraj_classic',
        'temple_of_ahnqiraj_sod' => 'temple_of_ahnqiraj_classic',
    ];

    /** @var array<string, array<int, string>> Table => columns holding a floor id. */
    private const array FLOOR_ID_COLUMNS = [
        'arrows'                         => ['floor_id'],
        'brushlines'                     => ['floor_id'],
        'dungeon_floor_switch_markers'   => ['floor_id', 'source_floor_id', 'target_floor_id'],
        'dungeon_route_thumbnail_jobs'   => ['floor_id'],
        'dungeon_route_thumbnails'       => ['floor_id'],
        'dungeon_speedrun_required_npcs' => ['floor_id'],
        'dungeon_starts'                 => ['floor_id'],
        'enemies'                        => ['floor_id'],
        'enemy_forces_checkpoints'       => ['floor_id'],
        'enemy_packs'                    => ['floor_id'],
        'enemy_patrols'                  => ['floor_id'],
        'floor_union_areas'              => ['floor_id'],
        'floor_unions'                   => ['floor_id', 'target_floor_id'],
        'kill_zones'                     => ['floor_id'],
        'map_icons'                      => ['floor_id'],
        'mountable_areas'                => ['floor_id'],
        'paths'                          => ['floor_id'],
        'prideful_enemies'               => ['floor_id'],
    ];

    /** @var array<string, string> Table => column holding a dungeon id, where a dungeon may appear any number of times. */
    private const array DUNGEON_ID_COLUMNS = [
        'affix_group_ease_tiers'        => 'dungeon_id',
        'dungeon_routes'                => 'dungeon_id',
        'dungeon_speedrun_difficulties' => 'dungeon_id',
        'dungeon_starts'                => 'target_dungeon_id',
        'mapping_change_logs'           => 'dungeon_id',
        'mapping_versions'              => 'dungeon_id',
        'users'                         => 'dungeon_id',
    ];

    /** @var array<string, string> Table => the other column that, with dungeon_id, identifies a row. */
    private const array DUNGEON_PAIR_TABLES = [
        'npc_dungeons'    => 'npc_id',
        'season_dungeons' => 'season_id',
        'spell_dungeons'  => 'spell_id',
    ];

    /** @var array<int, string> Floor columns the Season of Discovery copy has right and the Classic one does not. */
    private const array FLOOR_COLUMNS_FROM_SOURCE = [
        'ui_map_id',
        'ingame_min_x',
        'ingame_min_y',
        'ingame_max_x',
        'ingame_max_y',
    ];

    /**
     * Ruins and Temple of Ahn'Qiraj each existed twice: a Classic dungeon and a Season of Discovery copy
     * added before SoD had its own game version. Since SoD falls back to Classic, the SoD game version
     * listed both. This folds each SoD copy into its Classic dungeon, which keeps its own id, key and slug
     * and takes over the SoD mapping versions.
     *
     * Both copies share the same floor layout and map tiles, so every SoD floor is matched to the Classic
     * floor at the same index and everything on it is repointed there. The SoD copy carries the real game
     * ids (zone, map, instance, uiMap) and in-game floor bounds, so the Classic dungeon takes those over.
     *
     * The seeder rebuilds the mapping tables from JSON that already reflects this merge. The migration
     * repoints those tables too, so the database is consistent in the window between the migration and
     * the seed run, while the old containers still serve.
     */
    public function up(): void
    {
        foreach (self::MERGES as $sourceKey => $targetKey) {
            $source = DB::table('dungeons')->where('key', $sourceKey)->first();
            $target = DB::table('dungeons')->where('key', $targetKey)->first();

            if ($source === null || $target === null) {
                continue;
            }

            DB::transaction(fn() => $this->mergeDungeon($source, $target));
        }
    }

    /**
     * Not reversible: the Season of Discovery dungeons and their floors are deleted.
     */
    public function down(): void
    {
    }

    private function mergeDungeon(object $source, object $target): void
    {
        $floorIdMap = $this->mergeFloors($source->id, $target->id);

        foreach (self::FLOOR_ID_COLUMNS as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                foreach ($floorIdMap as $sourceFloorId => $targetFloorId) {
                    DB::table($table)->where($column, $sourceFloorId)->update([$column => $targetFloorId]);
                }
            }
        }

        foreach (self::DUNGEON_ID_COLUMNS as $table => $column) {
            DB::table($table)->where($column, $source->id)->update([$column => $target->id]);
        }

        foreach (self::DUNGEON_PAIR_TABLES as $table => $otherColumn) {
            $targetOtherIds = DB::table($table)->where('dungeon_id', $target->id)->pluck($otherColumn);

            DB::table($table)
                ->where('dungeon_id', $source->id)
                ->whereIn($otherColumn, $targetOtherIds)
                ->delete();
            DB::table($table)->where('dungeon_id', $source->id)->update(['dungeon_id' => $target->id]);
        }

        DB::table('page_views')
            ->where('model_class', Dungeon::class)
            ->where('model_id', $source->id)
            ->update(['model_id' => $target->id]);

        DB::table('dungeons')->where('id', $target->id)->update([
            'zone_id'     => $source->zone_id,
            'map_id'      => $source->map_id,
            'instance_id' => $source->instance_id,
            'views'       => $target->views + $source->views,
            'active'      => $target->active || $source->active,
        ]);
        DB::table('dungeons')->where('id', $source->id)->delete();
    }

    /**
     * Moves the source dungeon's floors onto the target's floor at the same index, deleting the source
     * floors and their couplings - the target's own couplings already connect the same floors. A source
     * floor without a counterpart moves to the target dungeon as it is.
     *
     * @return Collection<int, int> Source floor id => target floor id.
     */
    private function mergeFloors(int $sourceDungeonId, int $targetDungeonId): Collection
    {
        $targetFloors = DB::table('floors')->where('dungeon_id', $targetDungeonId)->get()->keyBy('index');
        $sourceFloors = DB::table('floors')->where('dungeon_id', $sourceDungeonId)->get();

        $floorIdMap = collect();
        foreach ($sourceFloors as $sourceFloor) {
            $targetFloor = $targetFloors->get($sourceFloor->index);

            if ($targetFloor === null) {
                DB::table('floors')->where('id', $sourceFloor->id)->update(['dungeon_id' => $targetDungeonId]);

                continue;
            }

            DB::table('floors')->where('id', $targetFloor->id)->update(
                collect(self::FLOOR_COLUMNS_FROM_SOURCE)
                    ->mapWithKeys(static fn(string $column) => [$column => $sourceFloor->{$column}])
                    ->all(),
            );

            $floorIdMap->put($sourceFloor->id, $targetFloor->id);
        }

        DB::table('floor_couplings')
            ->where(static function ($query) use ($floorIdMap) {
                $query->whereIn('floor1_id', $floorIdMap->keys())
                    ->orWhereIn('floor2_id', $floorIdMap->keys());
            })
            ->delete();
        DB::table('floors')->whereIn('id', $floorIdMap->keys())->delete();

        return $floorIdMap;
    }
};
