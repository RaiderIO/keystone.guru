<?php

namespace App\Console\Commands\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

class BackfillDungeonStartId extends Command
{
    private const float LAT_LNG_EPSILON = 0.000001;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeonroute:backfilldungeonstartid
        {--dir= : Seeder dungeon data directory holding the dungeon_starts.json files, defaults to database/seeders/dungeondata}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Points dungeon_start_id of every route at the dungeon start its dungeon_start_map_icon_id map icon became, matched on mapping version, floor and position.';

    public function handle(): int
    {
        $dungeonStartsByKey = $this->readDungeonStarts($this->option('dir') ?? database_path('seeders/dungeondata'));

        $updated   = 0;
        $unmatched = 0;
        DungeonRoute::query()
            ->whereNotNull('dungeon_start_map_icon_id')
            ->whereNull('dungeon_start_id')
            ->select(['id', 'dungeon_start_map_icon_id'])
            ->chunkById(500, function (Collection $dungeonRoutes) use ($dungeonStartsByKey, &$updated, &$unmatched): void {
                /** @var Collection<int, MapIcon> $mapIconsById */
                $mapIconsById = MapIcon::query()
                    ->without(['mapIconType', 'linkedawakenedobelisks'])
                    ->whereIn('id', $dungeonRoutes->pluck('dungeon_start_map_icon_id'))
                    ->where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START])
                    ->get(['id', 'mapping_version_id', 'floor_id', 'lat', 'lng'])
                    ->keyBy('id');

                foreach ($dungeonRoutes as $dungeonRoute) {
                    /** @var DungeonRoute $dungeonRoute */
                    $mapIcon        = $mapIconsById->get($dungeonRoute->dungeon_start_map_icon_id);
                    $dungeonStartId = $mapIcon === null ? null : $this->findDungeonStartId($dungeonStartsByKey, $mapIcon);

                    if ($dungeonStartId === null) {
                        $unmatched++;

                        continue;
                    }

                    DungeonRoute::query()->whereKey($dungeonRoute->id)->update(['dungeon_start_id' => $dungeonStartId]);
                    $updated++;
                }
            });

        $this->info(sprintf('Backfilled %d route(s), %d route(s) without a matching dungeon start', $updated, $unmatched));

        return self::SUCCESS;
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
    private function findDungeonStartId(array $dungeonStartsByKey, MapIcon $mapIcon): ?int
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
}
