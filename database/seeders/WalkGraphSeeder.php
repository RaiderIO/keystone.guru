<?php

namespace Database\Seeders;

use App\Models\Floor\Floor;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Loads the walk graphs committed in `dungeondata/<expansion>/<dungeon>/walk_graphs.json`, an array with one entry per
 * ui map, into `walk_graphs`. A dungeon without seeded graphs has no file. Production builds these graphs from its own combat logs, so this never swaps or truncates the table:
 * a combat-log graph with at least the minimum number of runs is left alone, anything else is replaced by the file.
 */
class WalkGraphSeeder extends Seeder implements TableSeederInterface
{
    public const string FILE_NAME = 'walk_graphs.json';

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
        return basename($filePath) === self::FILE_NAME;
    }

    public function run(): void
    {
        $rootDir   = $this->rootDir ?? database_path('seeders/dungeondata');
        $filePaths = glob(sprintf('%s/*/*/%s', rtrim($rootDir, '/'), self::FILE_NAME)) ?: [];
        sort($filePaths);

        $minRuns = (int)config('keystoneguru.walk_graph.min_runs');

        DB::transaction(function () use ($filePaths, $minRuns) {
            $seededCount = 0;
            $entryCount  = 0;
            foreach ($filePaths as $filePath) {
                $entries = json_decode(File::get($filePath), true);
                if (!is_array($entries) || !array_is_list($entries)) {
                    $this->command?->warn(sprintf('- Skipping %s: not a JSON array of walk graphs', $filePath));

                    continue;
                }

                foreach ($entries as $index => $entry) {
                    $entryCount++;
                    if ($this->seedEntry($entry, sprintf('%s[%d]', $filePath, $index), $minRuns)) {
                        $seededCount++;
                    }
                }
            }

            $this->command?->info(sprintf('- Seeded %d of %d walk graph(s)', $seededCount, $entryCount));
        });
    }

    /**
     * @param  mixed  $data        One entry of a `walk_graphs.json` array.
     * @param  string $description Where the entry lives, for the warnings.
     * @return bool   True when the entry's graph was written.
     */
    private function seedEntry(mixed $data, string $description, int $minRuns): bool
    {
        if (!is_array($data) || array_diff(self::REQUIRED_KEYS, array_keys($data)) !== []) {
            $this->command?->warn(sprintf('- Skipping walk graph %s: missing keys', $description));

            return false;
        }

        $edges = base64_decode((string)$data['edges'], true);
        if ($edges === false) {
            $this->command?->warn(sprintf('- Skipping walk graph %s: edges are not valid base64', $description));

            return false;
        }

        $uiMapId = (int)$data['ui_map_id'];
        if (!Floor::query()->where('ui_map_id', $uiMapId)->exists()) {
            $this->command?->warn(sprintf('- Skipping walk graph %s: no floor uses ui map %d', $description, $uiMapId));

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

        if ($this->writeGraph($uiMapId, $attributes, $minRuns)) {
            return true;
        }

        $this->command?->info(sprintf('- Left the existing walk graph of ui map %d in place', $uiMapId));

        return false;
    }

    /**
     * Writes the graph unless the ui map has a combat log graph with at least the minimum number of runs. The check is
     * part of the write itself, so a combat log build finishing concurrently is never overwritten.
     *
     * @param  array<string, mixed> $attributes
     * @return bool                 True when the graph was written.
     */
    public function writeGraph(int $uiMapId, array $attributes, int $minRuns): bool
    {
        $updatedCount = WalkGraph::query()
            ->where('ui_map_id', $uiMapId)
            ->where(static function (Builder $query) use ($minRuns) {
                $query->where('source', WalkGraphSource::Seeder->value)
                    ->orWhere('run_count', '<', $minRuns);
            })
            ->update($attributes);
        if ($updatedCount > 0) {
            return true;
        }

        try {
            WalkGraph::query()->insert(array_merge($attributes, [
                'ui_map_id'  => $uiMapId,
                'created_at' => $attributes['updated_at'],
            ]));
        } catch (UniqueConstraintViolationException) {
            // The ui map already has a graph the guarded update would not replace
            return false;
        }

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
