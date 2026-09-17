<?php

namespace App\Service\Cache;

use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use App\Service\Cache\Redis\RedisServiceInterface;
use Closure;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;

class CacheService implements CacheServiceInterface
{
    private const int PRESENCE_KEY_IDLE_SECONDS = 86400;

    private bool $cacheEnabled = true;

    /** @var bool Bypassing the cache means that the closure is always called and the result is never cached */
    private bool $bypassCache = false;

    public function __construct(
        private readonly RedisServiceInterface        $redisService,
        private readonly CacheServiceLoggingInterface $log,
    ) {
    }

    private function getTtl(string $key): ?DateInterval
    {
        $cacheConfig = config('keystoneguru.cache');

        return isset($cacheConfig[$key]) ? DateInterval::createFromDateString($cacheConfig[$key]['ttl']) : null;
    }

    /**
     * Converts a TTL - an int of seconds, a '1 hour'-style string, or a DateInterval - into a number of seconds
     * suitable for a Redis EXPIRE. Returns null when no TTL is given (the key should not expire).
     */
    private function ttlToSeconds(mixed $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return $ttl;
        }

        if (is_string($ttl)) {
            $ttl = DateInterval::createFromDateString($ttl);
        }

        if ($ttl instanceof DateInterval) {
            $reference = new DateTimeImmutable();

            return $reference->add($ttl)->getTimestamp() - $reference->getTimestamp();
        }

        return null;
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
                    } catch (InvalidArgumentException|RedisException $e) {
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
    }

    /**
     * @param  Closure|mixed                 $value
     * @param  bool|array<int, class-string> $allowedClasses
     * @return mixed
     */
    public function rememberInHash(string $hashKey, string $field, mixed $value, mixed $ttl = null, bool|array $allowedClasses = false): mixed
    {
        $prefixedKey = config('database.redis.options.prefix') . $hashKey;
        $redis       = Redis::connection('default');

        // Return the cached field when it exists and the cache is enabled. A missing field is false (phpredis)
        // or null (predis); a genuinely stored null is distinguishable as the serialized string 'N;'.
        if ($this->cacheEnabled) {
            $cached = $this->redisService->rawCommand($redis, 'HGET', $prefixedKey, $field);
            if ($cached !== false && $cached !== null) {
                return unserialize((string)$cached, ['allowed_classes' => $allowedClasses]);
            }
        }

        // Cache miss - resolve the value by calling the closure
        if ($value instanceof Closure) {
            $value = $value();
        }

        // Only write it to cache when we're not bypassing it
        if (!$this->isBypassCache()) {
            $this->redisService->rawCommand($redis, 'HSET', $prefixedKey, $field, serialize($value));

            // Redis can only expire a hash as a whole, so refresh the TTL of the entire hash on every write.
            // A frequently viewed route thus keeps all of its cached variants warm together.
            $ttlSeconds = $this->ttlToSeconds($ttl);
            if ($ttlSeconds !== null) {
                $this->redisService->rawCommand($redis, 'EXPIRE', $prefixedKey, (string)$ttlSeconds);
            }
        }

        return $value;
    }

    /**
     * Drops an entire hash - and every field within it - in a single Redis DEL, regardless of how many fields
     * exist. This is what makes dropping a route's card caches a bounded operation.
     */
    public function dropHashCache(string $hashKey): void
    {
        $prefixedKey = config('database.redis.options.prefix') . $hashKey;

        $this->redisService->rawCommand(Redis::connection('default'), 'DEL', $prefixedKey);
    }

    public function get(string $key): mixed
    {
        // Called from TrustProxies on every production request (via CloudflareService::getIpRanges()),
        // so an uncaught RedisException here would take the whole site down on a Redis blip (#3914).
        try {
            return Cache::get($key);
        } catch (RedisException $e) {
            $this->log->getFailedRedisConnection($key, $e);

            return null;
        }
    }

    /**
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
            sprintf('/%smdt_enemies_[a-z_]+_\d+/', $prefix),
            // Cards - one hash per route, keyed dungeonroute_card:{id}
            sprintf('/%sdungeonroute_card:\d+/', $prefix),
            // Dungeon data used in MapContext
            sprintf('/%sdungeon_\d+_\d+_[a-z_]+/', $prefix),
            // Per-region view variables, keyed by release: view_variables:{release}:game_server_region:{short}
            sprintf('/%sview_variables:[a-z0-9.]+:game_server_region:[a-z]+/', $prefix),
            // Granular global view data, keyed by release: view_data:{release}:{name}
            sprintf('/%sview_data:[a-z0-9.]+:[a-z_]+/', $prefix),
        ]);
    }

    public function clearIdleKeys(): int
    {
        $prefix = config('database.redis.options.prefix') . config('cache.prefix');

        // Presence keys are only ever written on the 'default' broadcasting connection (see
        // config/broadcasting.php), so the sweep for them does not need the 'cache' connection. A SCAN MATCH
        // also lets Redis itself discard everything but presence keys, instead of returning every key on the
        // connection for PHP to regex against.
        return $this->deleteKeysByPattern(
            [
                // publicKeys are 7 characters long
                sprintf('/%spresence-%s-(?:route-edit|live-session)\.[a-zA-Z0-9]{7}.*/', $prefix, config('app.type')),
            ],
            self::PRESENCE_KEY_IDLE_SECONDS,
            connections: ['default'],
            scanMatch: sprintf('%spresence-%s-*', $prefix, config('app.type')),
        );
    }

    public function lock(string $key, callable $callable, int $waitFor = 10): mixed
    {
        return Cache::lock($key, 10, 'default')->block($waitFor, $callable);
    }

    /**
     * @param array<int, string>      $regexes
     * @param array<int, string>|null $connections Overrides the default connection list. The 'session'
     *                                             connection may never be included, so this task can never
     *                                             delete active sessions and log idle users out.
     */
    private function deleteKeysByPattern(
        array   $regexes,
        ?int    $idleTimeSeconds = null,
        ?array  $connections = null,
        ?string $scanMatch = null,
    ): int {
        if (empty($regexes)) {
            return 0;
        }

        $connections ??= [
            // App logic
            'default',
            // Used by Laravel cache
            'cache',
        ];

        // Get the key prefix (if any)
        $prefix = config('database.redis.options.prefix');

        $totalDeletedCount = 0;

        foreach ($connections as $connection) {
            // Get the Redis connection once.
            $redis                         = Redis::connection($connection);
            $deletedCountForThisConnection = $this->deleteKeysByPatternOnConnection($redis, $regexes, $idleTimeSeconds, $prefix, $scanMatch);
            $totalDeletedCount += $deletedCountForThisConnection;
        }

        return $totalDeletedCount;
    }

    /**
     * @param array<int, string> $regexes
     */
    private function deleteKeysByPatternOnConnection(
        Connection $redis,
        array      $regexes,
        ?int       $idleTimeSeconds,
        string     $prefix,
        ?string    $scanMatch = null,
    ): int {
        $deletedKeysCountTotal = 0;
        $deletedKeysCount      = 0;
        $i                     = 0;
        $nextKey               = 0;

        // SCAN's own MATCH filters keys on the Redis server, so a caller with a narrow glob pattern (e.g. a
        // fixed key prefix) never pays for PHP regex-matching every key on the connection. COUNT 1000 keeps
        // the loop to a handful of round trips instead of the ~10-per-call default.
        $scanArgs = $scanMatch !== null
            ? ['MATCH', $scanMatch, 'COUNT', '1000']
            : [];

        try {
            $this->log->deleteKeysByPatternStart($redis->getName(), $idleTimeSeconds);

            do {
                $result = $this->redisService->rawCommand($redis, 'SCAN', (string)$nextKey, ...$scanArgs);

                if ($result === false) {
                    $this->log->deleteKeysByPatternScanFailed($nextKey);
                    break;
                }

                $nextKey  = (int)$result[0];
                $toDelete = [];

                // Iterate over the keys returned by SCAN
                foreach ($result[1] as $redisKey) {
                    foreach ($regexes as $regex) {
                        if (preg_match($regex, (string)$redisKey)) {
                            // If an idle time is provided, check key's idle time.
                            if ($idleTimeSeconds !== null) {
                                $keyIdleTimeSeconds = $this->redisService->rawCommand($redis, 'OBJECT', 'idletime', (string)$redisKey);

                                if ($keyIdleTimeSeconds > $idleTimeSeconds) {
                                    $toDelete[] = (string)$redisKey;
                                }
                            } else {
                                $toDelete[] = (string)$redisKey;
                            }
                            // Break once a match is found on a key for one regex.
                            break;
                        }
                    }
                }

                if (!empty($toDelete)) {
                    // Delete the exact keys returned by SCAN (avoid prefix double-application differences).
                    $nrOfDeletedKeys = $this->redisService->rawCommand($redis, 'DEL', ...$toDelete);

                    $deletedKeysCount += (int)$nrOfDeletedKeys;

                    if ((int)$nrOfDeletedKeys !== count($toDelete)) {
                        $this->log->deleteKeysByPatternFailedToDeleteAllKeys((int)$nrOfDeletedKeys, count($toDelete));
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
