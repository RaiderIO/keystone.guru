<?php


namespace App\Service\Cache;

use App\Models\Dungeon;
use DateInterval;
use Illuminate\Support\Facades\Cache;

class CacheService implements CacheServiceInterface
{
    private function _getTtl(string $key): ?DateInterval
    {
        $cacheConfig = config('keystoneguru.cache');

        return isset($cacheConfig[$key]) ? DateInterval::createFromDateString($cacheConfig[$key]['ttl']) : null;
    }

    public function getOtherwiseSet(string $key, $closure, $ttl = null)
    {
        $result = null;

        // Will never get triggered if
        if ($this->has($key)) {
            $result = $this->get($key);
        } // When in debug, don't do any caching
        else {
            // Get the result by calling the closure
            $result = $closure();
            // Only write it to cache when we're not in debug mode
            if (!env('APP_DEBUG')) {
                // If not overridden, get the TTL from config, if it's set anyways
                $this->set($key, $result, $ttl ?? $this->_getTtl($key));
            }
        }

        return $result;
    }

    public function get(string $key)
    {
        return Cache::get($key);
    }

    public function set(string $key, $object, $ttl = null): bool
    {
        return Cache::set($key, $object, $ttl);
    }

    public function unset(string $key): bool
    {
        return Cache::delete($key);
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    public function dropCaches(): void
    {
        $keys = array_keys(config('keystoneguru.cache'));
        foreach ($keys as $key) {
            $this->unset($key);
        }

        foreach (Dungeon::all() as $dungeon) {
            $this->unset(sprintf('dungeon_%s', $dungeon->id));
        }
    }


}