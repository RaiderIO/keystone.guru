<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Isolates one paratest worker from the others. paratest exports TEST_TOKEN=1..N to each worker
 * process; without a token nothing here changes.
 */
trait UsesParallelTestToken
{
    private static bool $parallelProcessEnvironmentPrepared = false;

    /**
     * The paratest worker token, or null when not running under paratest.
     */
    public static function parallelTestToken(): ?string
    {
        $token = $_SERVER['TEST_TOKEN'] ?? $_ENV['TEST_TOKEN'] ?? getenv('TEST_TOKEN');
        $token = is_string($token) ? trim($token) : '';

        return $token === '' ? null : $token;
    }

    /**
     * `keystone.guru.dev` + `2` → `keystone.guru.dev_2`; no token → the base name unchanged.
     */
    public static function resolveParallelSchemaName(string $base, ?string $token): string
    {
        if ($token === null || $token === '') {
            return $base;
        }

        return sprintf('%s_%s', $base, $token);
    }

    /**
     * A file path gets the token before its extension (`/tmp/services.php` → `/tmp/services_2.php`),
     * a directory path gets it as a subdirectory (`/tmp/cache` → `/tmp/cache/2`).
     */
    public static function resolveParallelPath(string $base, ?string $token): string
    {
        if ($token === null || $token === '') {
            return $base;
        }

        $extension = pathinfo($base, PATHINFO_EXTENSION);
        if ($extension === '') {
            return sprintf('%s/%s', rtrim($base, '/'), $token);
        }

        return sprintf('%s/%s_%s.%s', dirname($base), pathinfo($base, PATHINFO_FILENAME), $token, $extension);
    }

    /**
     * Must run before the application is created: APP_SERVICES_CACHE is read while the kernel
     * bootstraps, and workers building the same manifest file at once can read a half-written one.
     */
    protected function prepareParallelProcessEnvironment(string $token): void
    {
        if (self::$parallelProcessEnvironmentPrepared) {
            return;
        }
        self::$parallelProcessEnvironmentPrepared = true;

        $servicesCache = $_SERVER['APP_SERVICES_CACHE'] ?? $_ENV['APP_SERVICES_CACHE'] ?? getenv('APP_SERVICES_CACHE');
        if (is_string($servicesCache) && $servicesCache !== '') {
            $_SERVER['APP_SERVICES_CACHE'] = self::resolveParallelPath($servicesCache, $token);
        }
    }

    /**
     * Points both test schemas, the Redis key prefix and the tmp_file cache directory at a per-token
     * copy. Runs every test, since the app and its config are rebuilt per test.
     */
    protected function isolateParallelTestProcess(string $token): void
    {
        $tmpFileCachePath = self::resolveParallelPath(config('cache.stores.tmp_file.path'), $token);
        $phpunitDatabase  = self::resolveParallelSchemaName(config('database.connections.phpunit.database'), $token);

        // Tests\Bootstrap migrates combatlog_phpunit by its own name when one is configured (worktrees)
        $combatlogPhpunitDatabase = config('database.connections.combatlog_phpunit.database');
        if (!empty($combatlogPhpunitDatabase)) {
            config([
                'database.connections.combatlog_phpunit.database' => self::resolveParallelSchemaName($combatlogPhpunitDatabase, $token),
                'database.connections.combatlog_phpunit.url'      => null,
            ]);
            DB::purge('combatlog_phpunit');
        }

        // App\Models\User pins itself to the `mysql` connection, which in CI names the phpunit schema
        config([
            'database.connections.phpunit.database'   => $phpunitDatabase,
            'database.connections.phpunit.url'        => null,
            'database.connections.mysql.database'     => $phpunitDatabase,
            'database.connections.mysql.url'          => null,
            'database.connections.combatlog.database' => self::resolveParallelSchemaName(config('database.connections.combatlog.database'), $token),
            'database.connections.combatlog.url'      => null,
            'database.redis.options.prefix'           => sprintf('%s%s:', config('database.redis.options.prefix'), $token),
            'cache.stores.tmp_file.path'              => $tmpFileCachePath,
            'cache.stores.tmp_file.lock_path'         => $tmpFileCachePath,
        ]);

        DB::purge('phpunit');
        DB::purge('mysql');
        DB::purge('combatlog');
        // RedisManager snapshots database.redis when first resolved; drop any instance built before the prefix changed
        $this->app->forgetInstance('redis');
    }
}
