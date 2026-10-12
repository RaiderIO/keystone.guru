<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;

return new class extends Migration {
    private const float LAT_LNG_EPSILON = 0.000001;

    private const int LEGACY_DUNGEON_START_MAP_ICON_TYPE_ID = 10;

    public function up(): void
    {
        // Has to run while the legacy start map icons are still in the database: the seeder removes them
        $this->backfillDungeonStartIds(database_path('seeders/dungeondata'));

        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropIndex(['dungeon_start_map_icon_id']);
            $table->dropColumn('dungeon_start_map_icon_id');
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->unsignedBigInteger('dungeon_start_map_icon_id')->nullable()->after('published_state_id');
            $table->index('dungeon_start_map_icon_id');
        });
    }

    /**
     * Points dungeon_start_id of every route that only has a dungeon_start_map_icon_id (created by the code running
     * during the previous release's rollout) at the dungeon start that map icon became, matched on mapping version,
     * floor and position.
     *
     * @return array{updated: int, unmatched: int}
     */
    public function backfillDungeonStartIds(string $dungeonDataDir): array
    {
        $dungeonStartsByKey = $this->readDungeonStarts($dungeonDataDir);

        $updated   = 0;
        $unmatched = 0;
        DB::table('dungeon_routes')
            ->whereNotNull('dungeon_start_map_icon_id')
            ->whereNull('dungeon_start_id')
            ->select(['id', 'dungeon_start_map_icon_id'])
            ->chunkById(500, function (Collection $dungeonRoutes) use ($dungeonStartsByKey, &$updated, &$unmatched): void {
                $mapIconsById = DB::table('map_icons')
                    ->whereIn('id', $dungeonRoutes->pluck('dungeon_start_map_icon_id'))
                    ->where('map_icon_type_id', self::LEGACY_DUNGEON_START_MAP_ICON_TYPE_ID)
                    ->get(['id', 'mapping_version_id', 'floor_id', 'lat', 'lng'])
                    ->keyBy('id');

                foreach ($dungeonRoutes as $dungeonRoute) {
                    $mapIcon        = $mapIconsById->get($dungeonRoute->dungeon_start_map_icon_id);
                    $dungeonStartId = $mapIcon === null ? null : $this->findDungeonStartId($dungeonStartsByKey, $mapIcon);

                    if ($dungeonStartId === null) {
                        $unmatched++;

                        continue;
                    }

                    DB::table('dungeon_routes')->where('id', $dungeonRoute->id)->update(['dungeon_start_id' => $dungeonStartId]);
                    $updated++;
                }
            });

        return ['updated' => $updated, 'unmatched' => $unmatched];
    }

    /**
     * @return array<string, array<int, array{id: int, lat: float, lng: float}>> Keyed by "<mapping version id>-<floor id>"
     */
    private function readDungeonStarts(string $dir): array
    {
        $result = [];
        foreach (Finder::create()->files()->in($dir)->name('dungeon_starts.json') as $file) {
            foreach (json_decode($file->getContents(), true, 512, JSON_THROW_ON_ERROR) as $dungeonStart) {
                $key            = sprintf('%d-%d', $dungeonStart['mapping_version_id'], $dungeonStart['floor_id']);
                $result[$key][] = [
                    'id'  => (int)$dungeonStart['id'],
                    'lat' => (float)$dungeonStart['lat'],
                    'lng' => (float)$dungeonStart['lng'],
                ];
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<int, array{id: int, lat: float, lng: float}>> $dungeonStartsByKey
     */
    private function findDungeonStartId(array $dungeonStartsByKey, object $mapIcon): ?int
    {
        $key = sprintf('%d-%d', $mapIcon->mapping_version_id, $mapIcon->floor_id);
        foreach ($dungeonStartsByKey[$key] ?? [] as $dungeonStart) {
            if (abs($dungeonStart['lat'] - $mapIcon->lat) < self::LAT_LNG_EPSILON &&
                abs($dungeonStart['lng'] - $mapIcon->lng) < self::LAT_LNG_EPSILON) {
                return $dungeonStart['id'];
            }
        }

        return null;
    }
};
