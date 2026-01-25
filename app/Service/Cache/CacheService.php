<?php

namespace App\Service\Cache;

use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\SimpleCache\InvalidArgumentException;

class CacheService implements CacheServiceInterface
{
    private const int LOCK_BLOCK_TIMEOUT = 20;

    private bool $cacheEnabled = true;

    /** @var bool Bypassing the cache means that the closure is always called and the result is never cached */
    private bool $bypassCache = false;

    public function __construct(private readonly CacheServiceLoggingInterface $log)
    {
    }

    private function getTtl(string $key): ?DateInterval
    {
        $cacheConfig = config('keystoneguru.cache');

        return isset($cacheConfig[$key]) ? DateInterval::createFromDateString($cacheConfig[$key]['ttl']) : null;
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function setCacheEnabled(bool $cacheEnabled): CacheService
    {
        $this->cacheEnabled = $cacheEnabled;

        return $this;
    }

    public function isBypassCache(): bool
    {
        return $this->bypassCache;
    }

    public function setBypassCache(bool $bypassCache): CacheService
    {
        $this->bypassCache = $bypassCache;

        return $this;
    }

    /**
     * Remembers a value with a specific key if a condition is met
     *
     * @param  Closure|mixed      $value
     * @return Closure|mixed|null
     *
     */
    public function rememberWhen(bool $condition, string $key, mixed $value, mixed $ttl = null): mixed
    {
        if ($condition) {
            $value = $this->remember($key, $value, $ttl);
        } elseif ($value instanceof Closure) {
            $value = $value();
        }

        return $value;
    }

    /**
     * @param  Closure|mixed $value
     * @return mixed
     */
    public function remember(string $key, mixed $value, mixed $ttl = null): mixed
    {
        // So if we're caching something, do not populate the model cache for the queries inside $value()
        // Otherwise we're caching things twice, and that's not what we want
        // The results will likely explode the model cache (and redis usage as a result) so don't use it
        return app('model-cache')->runDisabled(function () use ($key, $value, $ttl) {
            $result = null;

            //        $lock = Cache::lock(sprintf('%s:lock', $key), 10);
            try {
                // Wait up to 20 seconds to acquire the lock...
                //            $lock->block(self::LOCK_BLOCK_TIMEOUT);

                // If we should ignore the cache, or if it's not found
                if (!$this->cacheEnabled || ($result = $this->get($key)) === null) {
                    // Get the result by calling the closure
                    if ($value instanceof Closure) {
                        $value = $value();
                    }

                    // Only write it to cache when we're not local
                    if (!$this->isBypassCache()) {
                        if (is_string($ttl)) {
                            $ttl = DateInterval::createFromDateString($ttl);
                        }

                        // If not overridden, get the TTL from config, if it's set anyway
                        try {
                            if ($this->set($key, $value, $ttl ?? $this->getTtl($key))) {
                                $result = $value;
                            }
                        } catch (InvalidArgumentException $e) {
                            $this->log->rememberFailedToSetCache($key, $e);

                            $result = $value;
                        }
                    } else {
                        $result = $value;
                    }
                }
            } catch (LockTimeoutException $e) {
                $this->log->rememberFailedToAcquireLock($key, $e);
            } finally {
                //            $lock->release();
            }

            return $result;
        });
    }

    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    /**
     * @param string|null $ttl
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function set(string $key, mixed $object, mixed $ttl = null): bool
    {
        return Cache::set($key, $object, $ttl);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function unset(string $key): bool
    {
        return Cache::delete($key);
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function dropCaches(): void
    {
        $keys = array_keys(config('keystoneguru.cache'));
        foreach ($keys as $key) {
            $this->unset($key);
        }

        // Clear all view caches for dungeonroutes - go through redis to drop all cards
        $prefix = config('database.redis.options.prefix');
        $this->deleteKeysByPattern([
            // MDT
            sprintf('/%smdt_npcs_[a-z_]+/', $prefix),
            sprintf('/%smdt_enemies_[a-z_]+/', $prefix),
            // Cards
            sprintf('/%sdungeonroute_card:(?>vertical|horizontal):[a-zA-Z_]+:[01]_[01]_[01]_\d+/', $prefix),
            // Dungeon data used in MapContext
            sprintf('/%sdungeon_\d+_\d+_[a-z_]+/', $prefix),
            sprintf('/%sview_variables:game_server_region:[a-z]+/', $prefix),
        ]);
        $this->unset('view_variables:global');
    }

    public function clearIdleKeys(?int $seconds = null): int
    {
        // Only keys starting with this prefix may be cleaned up by this task, ex.
        // keystoneguru-live-cache:d8123999fdd7267f49290a1f2bb13d3b154b452a:f723072f44f1e4727b7ae26316f3d61dd3fe3d33
        // keystoneguru-live-cache:p79vfrAn4QazxHVtLb5s4LssQ5bi6ZaWGNTMOblt
        $prefix = config('database.redis.options.prefix') . config('cache.prefix');

        return $this->deleteKeysByPattern([
            sprintf('/%s[a-zA-Z0-9]{40}(?::[a-z0-9]{40})*/', $prefix),
        ], $seconds) +
            $this->deleteKeysByPattern([
                // publicKeys are 7 characters long
                sprintf('/%spresence-%s-route-edit\.[a-zA-Z0-9]{7}.*/', $prefix, config('app.type')),
                sprintf('/%spresence-%s-live-session\.[a-zA-Z0-9]{7}.*/', $prefix, config('app.type')),
                // Special - these keys should be cleared after 24 hours, regardless of what $seconds say
            ], 86400);
    }

    public function lock(string $key, callable $callable, int $waitFor = 10): mixed
    {
        return Cache::lock($key, 10, 'default')->block($waitFor, $callable);
    }

    private function deleteKeysByPattern(array $regexes, ?int $idleTimeSeconds = null): int
    {
        if (empty($regexes)) {
            return 0;
        }

        // List of Redis connection names to operate on.
        $connections = [
            'default',
            // App logic
            'model_cache',
            // Model cache
            'cache',
            // Used by Laravel cache
        ];

        // Get the key prefix (if any)
        $prefix = config('database.redis.options.prefix');

        $totalDeletedCount = 0;

        foreach ($connections as $connection) {
            // Get the Redis connection once.
            $redis                         = Redis::connection($connection);
            $deletedCountForThisConnection = $this->deleteKeysByPatternOnConnection($redis, $regexes, $idleTimeSeconds, $prefix);
            $totalDeletedCount += $deletedCountForThisConnection;
        }

        return $totalDeletedCount;
    }

    private function deleteKeysByPatternOnConnection(
        Connection $redis,
        array      $regexes,
        ?int       $idleTimeSeconds,
        string     $prefix,
    ): int {
        $deletedKeysCountTotal = 0;
        $deletedKeysCount      = 0;
        $i                     = 0;
        $nextKey               = 0;

        try {
            $this->log->deleteKeysByPatternStart($redis->getName(), $idleTimeSeconds);

            do {
                // Use SCAN to iterate keys. The SCAN command returns [cursor, keys...]
                $result = $redis->command('SCAN', [$nextKey]);
                if ($result === false) {
                    break;
                }
                $nextKey = (int)$result[0];

                $toDelete = [];

                // Iterate over the keys returned by SCAN
                foreach ($result[1] as $redisKey) {
                    foreach ($regexes as $regex) {
                        if (preg_match($regex, (string)$redisKey)) {
                            // If an idle time is provided, check key's idle time.
                            if ($idleTimeSeconds !== null) {
                                $keyIdleTimeSeconds = $redis->command('OBJECT', [
                                    'idletime',
                                    $redisKey,
                                ]);
                                if ($keyIdleTimeSeconds > $idleTimeSeconds) {
                                    $toDelete[] = $redisKey;
                                }
                            } else {
                                $toDelete[] = $redisKey;
                            }
                            // Break once a match is found on a key for one regex.
                            break;
                        }
                    }
                }

                if (!empty($toDelete)) {
                    // Remove the prefix from each key if present.
                    $toDeleteWithoutPrefix = array_map(fn($key) => str_replace($prefix, '', $key), $toDelete);

                    // Delete the keys and sum up the count.
                    $nrOfDeletedKeys = $redis->command('DEL', $toDeleteWithoutPrefix);
                    $deletedKeysCount += $nrOfDeletedKeys;

                    if ($nrOfDeletedKeys !== count($toDelete)) {
                        $this->log->deleteKeysByPatternFailedToDeleteAllKeys($nrOfDeletedKeys, count($toDelete));
                    }
                }

                $i++;

                if ($i % 1000 === 0) {
                    $deletedKeysCountTotal += $deletedKeysCount;
                    $this->log->deleteKeysByPatternProgress($i, $deletedKeysCount);
                    $deletedKeysCount = 0;
                }
            } while ($nextKey > 0);

            // Final progress update
            $deletedKeysCountTotal += $deletedKeysCount;
        } finally {
            $this->log->deleteKeysByPatternEnd($deletedKeysCountTotal);
        }

        return $deletedKeysCountTotal;
    }
}
