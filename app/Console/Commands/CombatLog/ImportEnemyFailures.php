<?php

namespace App\Console\Commands\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pulls the Auto Route Creator enemy failures of one dungeon from another keystone.guru deployment (production by
 * default) into the local combat_log_route_enemy_failures table, so they can be analysed locally - the admin heatmap,
 * the cluster analysis and ad-hoc queries all work on the local table only. Optionally also downloads the request
 * bodies the failing routes were built from, for replay with combatlog:ingestcombatlogroutejson.
 *
 * Imported rows keep the remote dungeon_route_id / mapping_version_id. The former never resolves locally (the heatmap
 * sidebar's "matching routes" links stay empty for imported rows); the latter only matches when both databases were
 * seeded from the same seeder state.
 */
class ImportEnemyFailures extends Command
{
    private const int INSERT_CHUNK_SIZE = 500;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:importenemyfailures
        {dungeon : Dungeon key}
        {--host=production : Which deployment to pull from - a key of config keystoneguru.remote_hosts}
        {--credentials-file= : File holding one "user:password" line for HTTP Basic auth; read from stdin when omitted}
        {--mapping-version= : Only import failures of this (remote) mapping version id; local rows of that version are replaced instead of all of the dungeon\'s}
        {--since= : Only import failures recorded at or after this date/time}
        {--page-size=1000 : Rows per API request, at most 1000}
        {--download-post-bodies= : Also download every failing route\'s ARC request body as <dir>/<public key>.json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replaces the local combat log route enemy failures of a dungeon with those of another keystone.guru deployment.';

    public function handle(): int
    {
        $dungeonKey = (string)$this->argument('dungeon');
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()->where('key', $dungeonKey)->first();
        if ($dungeon === null) {
            $this->error(sprintf('Unknown dungeon key "%s"', $dungeonKey));

            return self::FAILURE;
        }

        $host    = (string)$this->option('host');
        $baseUrl = config(sprintf('keystoneguru.remote_hosts.%s.base_url', $host));
        if (!is_string($baseUrl) || $baseUrl === '') {
            $this->error(sprintf('Unknown host "%s" - add it to config keystoneguru.remote_hosts', $host));

            return self::FAILURE;
        }

        $credentials = $this->readCredentials();
        if ($credentials === null) {
            return self::FAILURE;
        }
        [$user, $password] = $credentials;

        $mappingVersionId = $this->option('mapping-version') === null ? null : (int)$this->option('mapping-version');
        $since            = $this->option('since') === null ? null : (string)$this->option('since');
        $pageSize         = max(1, min(1000, (int)$this->option('page-size')));
        $postBodiesDir    = $this->option('download-post-bodies') === null ? null : (string)$this->option('download-post-bodies');

        // Retry only what a retry can fix - a connection failure or a 5xx - never a 4xx (a 404 post body is an answer)
        $http = Http::withBasicAuth($user, $password)
            ->acceptJson()
            ->timeout(120)
            ->retry(
                2,
                500,
                static fn(Throwable $exception): bool => $exception instanceof ConnectionException ||
                    ($exception instanceof RequestException && $exception->response->serverError()),
                throw: false,
            );

        $failuresUrl = sprintf('%s/api/v1/combatlog/enemy-failures/%s', rtrim($baseUrl, '/'), $dungeon->slug);
        $this->info(sprintf('Importing enemy failures for %s (%d) from %s', $dungeonKey, $dungeon->id, $failuresUrl));

        $afterId          = 0;
        $importedCount    = 0;
        $localRowsDeleted = false;
        /** @var array<string, true> $routePublicKeys */
        $routePublicKeys = [];

        do {
            $query = array_filter([
                'after_id'           => $afterId,
                'limit'              => $pageSize,
                'mapping_version_id' => $mappingVersionId,
                'since'              => $since,
            ], static fn($value) => $value !== null);

            $response = $http->get($failuresUrl, $query);
            if (!$response->successful()) {
                $this->error(sprintf('Request failed: HTTP %d %s', $response->status(), $this->describeErrorResponse($response)));
                if ($localRowsDeleted) {
                    $this->warn(sprintf('The local rows were already replaced - %d rows imported before the failure, the rest is missing. Re-run to retry.', $importedCount));
                }

                return self::FAILURE;
            }

            /** @var array<string, mixed> $body */
            $body = $response->json();
            /** @var array<int, array<string, mixed>> $rows */
            $rows = $body['data'] ?? [];
            /** @var array{count?: int, next_after_id?: int|null, has_more?: bool} $meta */
            $meta = $body['meta'] ?? [];

            // Only once the remote answered do we touch the local table - a dead host or bad credentials must not
            // leave us with an empty table.
            if (!$localRowsDeleted) {
                $deleted = CombatLogRouteEnemyFailure::query()
                    ->where('dungeon_id', $dungeon->id)
                    ->when($mappingVersionId !== null, static fn($builder) => $builder->where('mapping_version_id', $mappingVersionId))
                    ->delete();
                $this->comment(sprintf('Deleted %d local rows', $deleted));
                $localRowsDeleted = true;
            }

            foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
                CombatLogRouteEnemyFailure::query()->insert(array_map(
                    static fn(array $row): array => [
                        'dungeon_route_id'   => $row['dungeon_route_id'],
                        'dungeon_id'         => $dungeon->id,
                        'floor_id'           => $row['floor_id'],
                        'mapping_version_id' => $row['mapping_version_id'],
                        'npc_id'             => $row['npc_id'],
                        'lat'                => $row['lat'],
                        'lng'                => $row['lng'],
                        'created_at'         => $row['created_at'],
                        'updated_at'         => $row['created_at'],
                    ],
                    $chunk,
                ));
            }

            foreach ($rows as $row) {
                if (!empty($row['dungeon_route_public_key'])) {
                    $routePublicKeys[$row['dungeon_route_public_key']] = true;
                }
            }

            $importedCount += count($rows);
            $this->comment(sprintf('Imported %d rows so far', $importedCount));

            $afterId = $meta['next_after_id'] ?? null;
        } while (($meta['has_more'] ?? false) && $afterId !== null);

        $this->info(sprintf('Imported %d enemy failures from %d distinct routes', $importedCount, count($routePublicKeys)));

        if ($postBodiesDir !== null) {
            $this->downloadPostBodies($http, rtrim($baseUrl, '/'), array_keys($routePublicKeys), $postBodiesDir);
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}|null [user, password], or null (after printing why) when none could be read
     */
    private function readCredentials(): ?array
    {
        $credentialsFile = $this->option('credentials-file');

        if ($credentialsFile !== null) {
            if (!is_readable((string)$credentialsFile)) {
                $this->error(sprintf('Credentials file %s does not exist or is not readable', $credentialsFile));

                return null;
            }

            $line = (string)file_get_contents((string)$credentialsFile);
        } else {
            if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
                $this->error('No --credentials-file given and nothing piped on stdin. Pipe a "user:password" line, e.g.: ' .
                    'docker compose exec -T app php artisan combatlog:importenemyfailures <dungeon> < ~/.config/keystone-guru/combatlog-production-basic-auth');

                return null;
            }

            $line = (string)stream_get_contents(STDIN);
        }

        $line = trim($line);
        if ($line === '' || !str_contains($line, ':')) {
            $this->error('Credentials must be a single "user:password" line');

            return null;
        }

        [$user, $password] = explode(':', $line, 2);

        return [$user, $password];
    }

    /**
     * @param string[] $publicKeys
     */
    private function downloadPostBodies(PendingRequest $http, string $baseUrl, array $publicKeys, string $directory): void
    {
        File::ensureDirectoryExists($directory);

        $downloaded = 0;
        $skipped    = 0;
        $missing    = 0;

        foreach ($publicKeys as $publicKey) {
            $target = sprintf('%s/%s.json', rtrim($directory, '/'), $publicKey);
            if (File::exists($target)) {
                $skipped++;

                continue;
            }

            $response = $http->get(sprintf('%s/api/v1/combatlog/route/%s/post-body', $baseUrl, $publicKey));
            if ($response->status() === 404) {
                $this->warn(sprintf('Route %s has no stored post body', $publicKey));
                $missing++;

                continue;
            }

            if (!$response->successful()) {
                $this->error(sprintf('Post body request for %s failed: HTTP %d %s', $publicKey, $response->status(), $this->describeErrorResponse($response)));

                continue;
            }

            File::put($target, $response->body());
            $downloaded++;
        }

        $this->info(sprintf('Post bodies: %d downloaded to %s, %d already present, %d without a stored body', $downloaded, $directory, $skipped, $missing));
    }

    private function describeErrorResponse(Response $response): string
    {
        $error = $response->json('error') ?? $response->json('message');

        return is_string($error) ? sprintf('(%s)', $error) : '';
    }
}
