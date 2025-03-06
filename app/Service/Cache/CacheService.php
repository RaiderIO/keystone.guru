<?php

namespace App\Service\Cache;

use App\Models\Dungeon;
use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\SimpleCache\InvalidArgumentException;

class CacheService implements CacheServiceInterface
{
    private const LOCK_BLOCK_TIMEOUT = 20;

    private bool $cacheEnabled = true;

    public function __construct(private readonly CacheServiceLoggingInterface $log)
    {

    }


    private function getTtl(string $key): ?DateInterval
    {
        $cacheConfig = config('keystoneguru.cache');

        return isset($cacheConfig[$key]) ? DateInterval::createFromDateString($cacheConfig[$key]['ttl']) : null;
    }

    public function setCacheEnabled(bool $cacheEnabled): CacheService
    {
//        $this->cacheEnabled = $cacheEnabled;

        return $this;
    }

    /**
     * Remembers a value with a specific key if a condition is met
     *
     * @param Closure|mixed $value
     * @return Closure|mixed|null
     *
     */
    public function rememberWhen(bool $condition, string $key, mixed $value, mixed $ttl = null): mixed
    {
        if ($condition) {
            $value = $this->remember($key, $value, $ttl);
        } else if ($value instanceof Closure) {
            $value = $value();
        }

        return $value;
    }

    /**
     * @param Closure|mixed $value
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
                if (config('app.env') !== 'local') {
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
        $prefix = config('database.redis.options.prefix');

        return $this->deleteKeysByPattern([
            sprintf('/%s[a-zA-Z0-9]{40}(?::[a-z0-9]{40})*/', $prefix),
        ], $seconds);
    }

    private function deleteKeysByPattern(array $regexes, ?int $idleTimeSeconds = null): int
    {
        if (empty($regexes)) {
            return 0;
        }

        // Only keys starting with this prefix may be cleaned up by this task, ex.
        $prefix = config('database.redis.options.prefix');

        $deletedKeysCountTotal = $deletedKeysCount = 0;
        try {
            $this->log->deleteKeysByPatternStart($idleTimeSeconds);
            $i       = 0;
            $nextKey = 0;

            do {
                $result = Redis::command('SCAN', [$nextKey]);

                $nextKey = (int)$result[0];

                $toDelete = [];
                foreach ($result[1] as $redisKey) {
                    $output = [];
                    foreach ($regexes as $regex) {
                        $matchResult = preg_match($regex, (string)$redisKey, $output);
                        if ($matchResult === false) {
                            $this->log->deleteKeysByPatternRegexError($regex, $redisKey);
                            break;
                        } else if ($matchResult > 0) {
                            // If we only want to delete keys that have a certain idle time, check that first
                            if ($idleTimeSeconds !== null) {
                                $keyIdleTimeSeconds = Redis::command('OBJECT', ['idletime', $redisKey]);
                                if ($keyIdleTimeSeconds > $idleTimeSeconds) {
                                    $toDelete[] = $redisKey;
                                }
                            } else {
                                // We don't, just delete it
                                $toDelete[] = $redisKey;
                            }

                            break;
                        }
                    }
                }

                if (!empty($toDelete)) {
                    $toDeleteCount = count($toDelete);

                    // https://redis.io/commands/del/
                    $toDeleteWithoutPrefix = [];
                    foreach ($toDelete as $key) {
                        $toDeleteWithoutPrefix[] = str_replace($prefix, '', $key);
                    }

                    $nrOfDeletedKeys  = Redis::command('DEL', $toDeleteWithoutPrefix);
                    $deletedKeysCount += $nrOfDeletedKeys;
                    if ($nrOfDeletedKeys !== $toDeleteCount) {
                        $this->log->deleteKeysByPatternFailedToDeleteAllKeys($nrOfDeletedKeys, $toDeleteCount);
                    }
                }

                $i++;
                if ($i % 1000 === 0) {
                    $deletedKeysCountTotal += $deletedKeysCount;
                    $this->log->deleteKeysByPatternProgress($i, $deletedKeysCount);
                    $deletedKeysCount = 0;
                }
            } while ($nextKey > 0);
        } finally {
            $this->log->deleteKeysByPatternEnd($deletedKeysCountTotal);
        }

        return $deletedKeysCount;
    }
}
