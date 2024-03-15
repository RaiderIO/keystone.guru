<?php

namespace App\Service\Cache;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Cache\Logging\CacheServiceLoggingInterface;
use Closure;
use DateInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Psr\SimpleCache\InvalidArgumentException;

class CacheService implements CacheServiceInterface
{
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
        $this->cacheEnabled = $cacheEnabled;

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

        // If we should ignore the cache, of if it's found
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
                    logger()->error($e->getMessage(), [
                        'exception' => $e,
                    ]);

                    $result = $value;
                }
            } else {
                $result = $value;
            }
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

        foreach (Dungeon::all() as $dungeon) {
            $this->unset(sprintf('dungeon_%d', $dungeon->id));
        }

        // Clear all view caches for dungeonroutes - use a simple query to prevent loading of all kinds of relations
        $dungeonRouteIds = collect(DB::select('SELECT `id` FROM dungeon_routes'))->pluck('id')->toArray();

        foreach ($dungeonRouteIds as $dungeonRouteId) {
            DungeonRoute::dropCaches($dungeonRouteId);
        }
    }

    public function clearIdleKeys(?int $seconds = null): int
    {
        // Only keys starting with this prefix may be cleaned up by this task, ex.
        // keystoneguru-live-cache:d8123999fdd7267f49290a1f2bb13d3b154b452a:f723072f44f1e4727b7ae26316f3d61dd3fe3d33
        // keystoneguru-live-cache:p79vfrAn4QazxHVtLb5s4LssQ5bi6ZaWGNTMOblt
        $prefix            = config('database.redis.options.prefix');
        $keyWhitelistRegex = [
            sprintf('/%s[a-z0-9]{40}(?::[a-z0-9]{40})*/', $prefix),
        ];

        try {
            $this->log->clearIdleKeysStart($seconds);
            $i                = 0;
            $nextKey          = 0;
            $deletedKeysCount = 0;

            do {
                $result = Redis::command('SCAN', [$nextKey]);

                $nextKey = (int)$result[0];

                $toDelete = [];
                foreach ($result[1] as $redisKey) {
                    $output = [];
                    foreach ($keyWhitelistRegex as $regex) {
                        $matchResult = preg_match($regex, (string)$redisKey, $output);
                        if ($matchResult === false) {
                            $this->log->clearIdleKeysRegexError($regex, $redisKey);
                            break;
                        } else if ($matchResult > 0) {
                            $idleTime = Redis::command('OBJECT', ['idletime', $redisKey]);
                            if ($idleTime > $seconds) {
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
                        $this->log->clearIdleKeysFailedToDeleteAllKeys($nrOfDeletedKeys, $toDeleteCount);
                    }
                }

                $i++;
                if ($i % 1000 === 0) {
                    $this->log->clearIdleKeysProgress($i, $deletedKeysCount);
                    $deletedKeysCount = 0;
                }
            } while ($nextKey > 0);
        } finally {
            $this->log->clearIdleKeysEnd();
        }

        return $deletedKeysCount;
    }
}
