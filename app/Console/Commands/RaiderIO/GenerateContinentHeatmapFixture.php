<?php

namespace App\Console\Commands\RaiderIO;

use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Service\RaiderIO\Mock\ContinentHeatmapFixtureGenerator;
use App\Service\RaiderIO\RaiderIOKeystoneGuruApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateContinentHeatmapFixture extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raiderio:generatecontinentheatmapfixture
        {mapId : The map_id of the continent (Eastern Kingdoms 0, Kalimdor 1)}
        {--cells-per-zone=6000 : Grid cells holding a death on every zone floor}
        {--seed=5619 : Seed for the random generator; the same seed produces the same fixture}
        {--output-dir= : Directory to write heatmap_grid_map_<mapId>.json to (default storage/app/raiderio_mock)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a full size continent heatmap fixture for the local mock Raider.IO service.';

    public function handle(ContinentHeatmapFixtureGenerator $generator): int
    {
        $mapId = (int)$this->argument('mapId');

        $dungeon = Dungeon::query()
            ->with('floors')
            ->where('map_id', $mapId)
            ->get()
            ->first(static fn(Dungeon $dungeon) => $dungeon->isContinent());

        if (!$dungeon instanceof Dungeon) {
            $this->error(sprintf('No continent found with map_id %d', $mapId));

            return self::FAILURE;
        }

        $grid = $generator->generate(
            $dungeon,
            (int)$this->option('cells-per-zone'),
            (int)config('keystoneguru.heatmap.service.data.player.size_x'),
            (int)config('keystoneguru.heatmap.service.data.player.size_y'),
            (int)$this->option('seed'),
        );

        $outputDir = $this->option('output-dir') ?: storage_path('app/raiderio_mock');
        File::ensureDirectoryExists($outputDir);
        $outputPath = sprintf('%s/%s', $outputDir, sprintf(RaiderIOKeystoneGuruApiService::CONTINENT_HEATMAP_FIXTURE_FILE_NAME, $mapId));

        File::put($outputPath, (string)json_encode([CombatLogEventEventType::PlayerDeath->value => $grid]));

        $this->info(sprintf(
            'Wrote %d zone floors, %d cells to %s',
            count($grid['gridsByFloor']),
            array_sum(array_map(static fn(array $rows) => intdiv(count($rows), 3), $grid['gridsByFloor'])),
            $outputPath,
        ));

        return self::SUCCESS;
    }
}
