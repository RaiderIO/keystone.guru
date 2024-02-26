<?php

namespace App\Service\Cache;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use Closure;
use DateInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

class CacheService implements CacheServiceInterface
{
    private bool $cacheEnabled = true;

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
     * @param Closure|mixed            $value
     * @param string|null|DateInterval $ttl
     * @return Closure|mixed|null
     *
     * @throws InvalidArgumentException
     */
    public function rememberWhen(bool $condition, string $key, $value, $ttl = null): mixed
    {
        if ($condition) {
            $value = $this->remember($key, $value, $ttl);
        } else if ($value instanceof Closure) {
            $value = $value();
        }

        return $value;
    }

    /**
     * @param Closure|mixed            $value
     * @param string|null|DateInterval $ttl
     */
    public function remember(string $key, $value, $ttl = null): mixed
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
     * @param string|null|DateInterval $ttl
     *
     * @throws InvalidArgumentException
     */
    public function set(string $key, $object, $ttl = null): bool
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
}
