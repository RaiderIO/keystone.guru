<?php

namespace App\Console\Commands\WalkGraph;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Downloads walk graphs from a keystone.guru deployment's `api/v1/walkgraphs` export and writes them as the
 * `dungeondata/<expansion>/<dungeon>/walk_graph_<uiMapId>.json` files `WalkGraphSeeder` loads. Every graph is
 * downloaded and checked before the first file is written, so a failure leaves the dungeon data untouched.
 */
class ImportWalkGraphs extends Command
{
    /** The seeder file's keys, in the order they are written so a refresh only changes the values. */
    private const array FILE_KEYS = [
        'ui_map_id',
        'cell_size',
        'run_count',
        'node_count',
        'edge_count',
        'largest_component_node_count',
        'built_at',
        'edges',
    ];

    private const string DIRECTORY_NAME_PATTERN = '/^[A-Za-z0-9_-]+$/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'walkgraph:import
        {--ui-map=* : Ui map id to import; repeatable}
        {--dungeon= : Dungeon key; imports every graph the export lists for this dungeon}
        {--dungeondata-path= : The dungeon data directory to write to; defaults to database/seeders/dungeondata}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads walk graphs from a keystone.guru deployment into the dungeon seeder files. Without --ui-map or --dungeon it imports every graph the export lists.';

    public function handle(): int
    {
        $authorization = $this->readAuthorization();
        if ($authorization === null) {
            return self::FAILURE;
        }

        $baseUrl = rtrim((string)config('keystoneguru.walk_graph.import_url'), '/');
        // Retry only what a retry can fix - a connection failure or a 5xx - never a 4xx
        $http = Http::withHeaders(['Authorization' => $authorization])
            ->acceptJson()
            ->timeout(120)
            ->retry(
                2,
                500,
                static fn(Throwable $exception): bool => $exception instanceof ConnectionException ||
                    ($exception instanceof RequestException && $exception->response->serverError()),
                throw: false,
            );

        $dungeonKey = $this->option('dungeon') === null ? null : (string)$this->option('dungeon');
        $uiMapIds   = $this->resolveUiMapIds($http, $baseUrl, $dungeonKey);
        if ($uiMapIds === null) {
            return self::FAILURE;
        }

        if ($uiMapIds === []) {
            $this->info(sprintf('%s exports no walk graphs, nothing to import', $baseUrl));

            return self::SUCCESS;
        }

        $dungeonDataPath = rtrim((string)($this->option('dungeondata-path') ?? database_path('seeders/dungeondata')), '/');

        /** @var array<string, string> $fileContents File path => contents */
        $fileContents = [];
        foreach ($uiMapIds as $uiMapId) {
            $walkGraphFile = $this->downloadWalkGraphFile($http, $baseUrl, $uiMapId, $dungeonKey, $dungeonDataPath);
            if ($walkGraphFile === null) {
                $this->error('No walk graph files were written');

                return self::FAILURE;
            }

            [$filePath, $contents]   = $walkGraphFile;
            $fileContents[$filePath] = $contents;
        }

        foreach ($fileContents as $filePath => $contents) {
            File::put($filePath, $contents);
            $this->info(sprintf('Wrote %s', $filePath));
        }

        return self::SUCCESS;
    }

    /**
     * @return string|null The `Authorization` header value, or null (after printing why) when there is none.
     */
    private function readAuthorization(): ?string
    {
        $credentialsPath = (string)config('keystoneguru.walk_graph.import_credentials_path');
        if (str_starts_with($credentialsPath, '~/')) {
            $credentialsPath = sprintf('%s%s', getenv('HOME') ?: '', substr($credentialsPath, 1));
        }

        if (!is_file($credentialsPath) || !is_readable($credentialsPath)) {
            $this->error(sprintf(
                'Credentials file %s does not exist or is not readable; point WALK_GRAPH_IMPORT_CREDENTIALS_PATH at it',
                $credentialsPath,
            ));

            return null;
        }

        $credentials = trim(File::get($credentialsPath));
        if (preg_match('/^(Basic|Bearer)\s+\S+$/i', $credentials) === 1) {
            return $credentials;
        }

        if (str_contains($credentials, ':') && preg_match('/[\r\n]/', $credentials) !== 1) {
            return sprintf('Basic %s', base64_encode($credentials));
        }

        $this->error(sprintf(
            'Credentials file %s must hold an Authorization header value ("Bearer <token>") or one "user:password" line',
            $credentialsPath,
        ));

        return null;
    }

    /**
     * @return array<int, int>|null The ui map ids to import, or null (after printing why) when they could not be resolved.
     */
    private function resolveUiMapIds(PendingRequest $http, string $baseUrl, ?string $dungeonKey): ?array
    {
        $uiMapIds = [];
        foreach ((array)$this->option('ui-map') as $uiMapId) {
            if (!ctype_digit((string)$uiMapId)) {
                $this->error(sprintf('Ui map id "%s" is not a number', $uiMapId));

                return null;
            }

            $uiMapIds[] = (int)$uiMapId;
        }

        if ($uiMapIds !== [] && $dungeonKey === null) {
            return array_values(array_unique($uiMapIds));
        }

        $index = $this->getData($http, sprintf('%s/api/v1/walkgraphs', $baseUrl), 'The walk graph index');
        if ($index === null) {
            return null;
        }

        $dungeonUiMapCount = 0;
        foreach ($index as $summary) {
            $summaryDungeonKeys = array_column(is_array($summary['dungeons'] ?? null) ? $summary['dungeons'] : [], 'key');
            if ($dungeonKey === null || in_array($dungeonKey, $summaryDungeonKeys, true)) {
                $uiMapIds[] = (int)($summary['ui_map_id'] ?? 0);
                $dungeonUiMapCount++;
            }
        }

        if ($dungeonKey !== null && $dungeonUiMapCount === 0) {
            $this->error(sprintf('%s exports no walk graph for dungeon %s', $baseUrl, $dungeonKey));

            return null;
        }

        return array_values(array_unique($uiMapIds));
    }

    /**
     * @return array{0: string, 1: string}|null [file path, contents], or null (after printing why) when the graph
     *                                          cannot be written.
     */
    private function downloadWalkGraphFile(
        PendingRequest $http,
        string         $baseUrl,
        int            $uiMapId,
        ?string        $dungeonKey,
        string         $dungeonDataPath,
    ): ?array {
        $description = sprintf('The walk graph of ui map %d', $uiMapId);
        $walkGraph   = $this->getData($http, sprintf('%s/api/v1/walkgraphs/%d', $baseUrl, $uiMapId), $description);
        if ($walkGraph === null) {
            return null;
        }

        $missingKeys = array_diff([...self::FILE_KEYS, 'dungeons'], array_keys($walkGraph));
        if ($missingKeys !== [] || base64_decode((string)$walkGraph['edges'], true) === false) {
            $this->error(sprintf('%s is malformed: missing %s or invalid base64 edges', $description, implode(', ', $missingKeys) ?: 'nothing'));

            return null;
        }

        $dungeons = is_array($walkGraph['dungeons']) ? array_values($walkGraph['dungeons']) : [];
        $dungeon  = collect($dungeons)->firstWhere('key', $dungeonKey) ?? $dungeons[0] ?? null;
        if (!is_array($dungeon)) {
            $this->error(sprintf('%s is not used by any dungeon', $description));

            return null;
        }

        $expansion = (string)($dungeon['expansion'] ?? '');
        $key       = (string)($dungeon['key'] ?? '');
        $directory = sprintf('%s/%s/%s', $dungeonDataPath, $expansion, $key);
        if (preg_match(self::DIRECTORY_NAME_PATTERN, $expansion) !== 1 ||
            preg_match(self::DIRECTORY_NAME_PATTERN, $key) !== 1 ||
            !is_dir($directory)) {
            $this->error(sprintf('%s belongs to dungeon directory %s, which does not exist', $description, $directory));

            return null;
        }

        $fileData = [
            'ui_map_id'                    => (int)$walkGraph['ui_map_id'],
            'cell_size'                    => (float)$walkGraph['cell_size'],
            'run_count'                    => (int)$walkGraph['run_count'],
            'node_count'                   => (int)$walkGraph['node_count'],
            'edge_count'                   => (int)$walkGraph['edge_count'],
            'largest_component_node_count' => (int)$walkGraph['largest_component_node_count'],
            'built_at'                     => (string)$walkGraph['built_at'],
            'edges'                        => (string)$walkGraph['edges'],
        ];

        return [
            sprintf('%s/walk_graph_%d.json', $directory, $uiMapId),
            sprintf(
                "%s\n",
                json_encode($fileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
            ),
        ];
    }

    /**
     * @return array<array-key, mixed>|null The response's `data`, or null (after printing why) when the request failed.
     */
    private function getData(PendingRequest $http, string $url, string $description): ?array
    {
        try {
            $response = $http->get($url);
        } catch (ConnectionException $exception) {
            $this->error(sprintf('%s could not be downloaded from %s: %s', $description, $url, $exception->getMessage()));

            return null;
        }

        if ($response->status() === 404) {
            $this->error(sprintf('%s does not exist on %s (HTTP 404)', $description, $url));

            return null;
        }

        if (in_array($response->status(), [401, 403], true)) {
            $this->error(sprintf(
                '%s was refused by %s (HTTP %d): the credentials are wrong or lack the admin or ai_agent role',
                $description,
                $url,
                $response->status(),
            ));

            return null;
        }

        $data = $response->json('data');
        if (!$response->successful() || !is_array($data)) {
            $this->error(sprintf('%s could not be downloaded from %s (HTTP %d)', $description, $url, $response->status()));

            return null;
        }

        return $data;
    }
}
