<?php

namespace Database\Seeders;

use App\Models\Floor\Floor;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Loads the walk graphs committed as `dungeondata/<expansion>/<dungeon>/walk_graph_<uiMapId>.json` into
 * `walk_graphs`. Production builds these graphs from its own combat logs, so this never swaps or truncates the table:
 * a combat-log graph with at least the minimum number of runs is left alone, anything else is replaced by the file.
 */
class WalkGraphSeeder extends Seeder implements TableSeederInterface
{
    private const string FILE_NAME_PATTERN = 'walk_graph_*.json';

    private const array REQUIRED_KEYS = [
        'ui_map_id',
        'cell_size',
        'run_count',
        'node_count',
        'edge_count',
        'largest_component_node_count',
        'built_at',
        'edges',
    ];

    /**
     * @param string|null $rootDir The dungeon data directory; defaults to `database/seeders/dungeondata`.
     */
    public function __construct(private readonly ?string $rootDir = null)
    {
    }

    public static function isWalkGraphFile(string $filePath): bool
    {
        return fnmatch(self::FILE_NAME_PATTERN, basename($filePath));
    }

    public function run(): void
    {
        $rootDir   = $this->rootDir ?? database_path('seeders/dungeondata');
        $filePaths = glob(sprintf('%s/*/*/%s', rtrim($rootDir, '/'), self::FILE_NAME_PATTERN)) ?: [];
        sort($filePaths);

        $minRuns = (int)config('keystoneguru.walk_graph.min_runs');

        DB::transaction(function () use ($filePaths, $minRuns) {
            $seededCount = 0;
            foreach ($filePaths as $filePath) {
                if ($this->seedFile($filePath, $minRuns)) {
                    $seededCount++;
                }
            }

            $this->command?->info(sprintf('- Seeded %d of %d walk graph(s)', $seededCount, count($filePaths)));
        });
    }

    /**
     * @return bool True when the file's graph was written.
     */
    private function seedFile(string $filePath, int $minRuns): bool
    {
        $data = json_decode(File::get($filePath), true);
        if (!is_array($data) || array_diff(self::REQUIRED_KEYS, array_keys($data)) !== []) {
            $this->command?->warn(sprintf('- Skipping walk graph %s: missing keys', $filePath));

            return false;
        }

        $edges = base64_decode((string)$data['edges'], true);
        if ($edges === false) {
            $this->command?->warn(sprintf('- Skipping walk graph %s: edges are not valid base64', $filePath));

            return false;
        }

        $uiMapId = (int)$data['ui_map_id'];
        if (!Floor::query()->where('ui_map_id', $uiMapId)->exists()) {
            $this->command?->warn(sprintf('- Skipping walk graph %s: no floor uses ui map %d', $filePath, $uiMapId));

            return false;
        }

        $attributes = [
            'source'                       => WalkGraphSource::Seeder->value,
            'cell_size'                    => (float)$data['cell_size'],
            'run_count'                    => (int)$data['run_count'],
            'node_count'                   => (int)$data['node_count'],
            'edge_count'                   => (int)$data['edge_count'],
            'largest_component_node_count' => (int)$data['largest_component_node_count'],
            'edges'                        => $edges,
            'built_at'                     => Carbon::parse($data['built_at'])->toDateTimeString(),
            'updated_at'                   => Carbon::now()->toDateTimeString(),
        ];

        /** @var WalkGraph|null $existingGraph */
        $existingGraph = WalkGraph::query()->where('ui_map_id', $uiMapId)->first(['id', 'source', 'run_count']);
        if ($existingGraph === null) {
            WalkGraph::query()->insert(array_merge($attributes, [
                'ui_map_id'  => $uiMapId,
                'created_at' => $attributes['updated_at'],
            ]));

            return true;
        }

        if ($existingGraph->source === WalkGraphSource::CombatLog && $existingGraph->run_count >= $minRuns) {
            $this->command?->info(sprintf(
                '- Keeping the combat log walk graph of ui map %d (%d runs)',
                $uiMapId,
                $existingGraph->run_count,
            ));

            return false;
        }

        WalkGraph::query()->whereKey($existingGraph->id)->update($attributes);

        return true;
    }

    public static function getAffectedModelClasses(): array
    {
        // Production's combat-log graphs live in the same table, so it is updated in place rather than swapped
        return [];
    }

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array
    {
        return null;
    }
}
