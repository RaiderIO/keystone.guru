<?php


namespace App\Service\Cache;

use App\Logic\Utils\Counter;
use App\Logic\Utils\Stopwatch;

class DevCacheService extends CacheService
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $result = parent::get($key);
        Counter::increase(sprintf('cacheservice[%s]:%s', $key, $result === null ? 'miss' : 'hit'));
        return $result;
    }

    public function rememberWhen(bool $condition, string $key, $value, $ttl = null)
    {
        $measureKey = sprintf('cacheservice-rememberwhen[%s]:%s', $key, $condition ? 'hit' : 'miss');
        Counter::increase($measureKey);
        Stopwatch::start($measureKey);
        $result = parent::rememberWhen($condition, $key, $value, $ttl);
        Stopwatch::pause($measureKey);
        return $result;
    }

    public function remember(string $key, $value, $ttl = null)
    {
        $measureKey = sprintf('cacheservice-remember[%s]', $key);
        Counter::increase($measureKey);
        Stopwatch::start($measureKey);
        $result = parent::remember($key, $value, $ttl);
        Stopwatch::pause($measureKey);
        return $result;
    }
}
