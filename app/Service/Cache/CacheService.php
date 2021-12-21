<?php


namespace App\Service\Cache;

use App\Models\Dungeon;
use App\Models\DungeonRoute;
use Closure;
use DateInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

class CacheService implements CacheServiceInterface
{
    /** @var bool */
    private bool $cacheEnabled = true;

    /**
     * @param string $key
     * @return DateInterval|null
     */
    private function getTtl(string $key): ?DateInterval
    {
        $cacheConfig = config('keystoneguru.cache');

        return isset($cacheConfig[$key]) ? DateInterval::createFromDateString($cacheConfig[$key]['ttl']) : null;
    }

    /**
     * @param bool $cacheEnabled
     * @return CacheService
     */
    public function setCacheEnabled(bool $cacheEnabled): CacheService
    {
        $this->cacheEnabled = $cacheEnabled;
        return $this;
    }

    /**
     * Remembers a value with a specific key if a condition is met
     * @param bool $condition
     * @param string $key
     * @param $value
     * @param null $ttl
     * @return Closure|mixed|null
     * @throws InvalidArgumentException
     */
    public function rememberWhen(bool $condition, string $key, $value, $ttl = null)
    {
        if ($condition) {
            $value = $this->remember($key, $value, $ttl);
        } else if ($value instanceof Closure) {
            $value = $value();
        }

        return $value;
    }

    /**
     * @param string $key
     * @param Closure|mixed $value
     * @param null $ttl
     * @return mixed
     */
    public function remember(string $key, $value, $ttl = null)
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
                // If not overridden, get the TTL from config, if it's set anyways
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

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return Cache::get($key);
    }

    /**
     * @param string $key
     * @param $object
     * @param null $ttl
     * @return bool
     * @throws InvalidArgumentException
     */
    public function set(string $key, $object, $ttl = null): bool
    {
        return Cache::set($key, $object, $ttl);
    }

    /**
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function unset(string $key): bool
    {
        return Cache::delete($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     *
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
