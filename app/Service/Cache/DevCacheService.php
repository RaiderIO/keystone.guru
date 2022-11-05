<?php


namespace App\Service\Cache;

use App\Logic\Utils\Counter;
use App\Logic\Utils\Stopwatch;
use Psr\SimpleCache\InvalidArgumentException;

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

    /**
     * @param bool $condition
     * @param string $key
     * @param Closure|mixed $value
     * @param string|null $ttl
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function rememberWhen(bool $condition, string $key, $value, ?string $ttl = null)
    {
        $measureKey = sprintf('cacheservice-rememberwhen[%s]:%s', $key, $condition ? 'hit' : 'miss');
        Counter::increase($measureKey);
        Stopwatch::start($measureKey);
        $result = parent::rememberWhen($condition, $key, $value, $ttl);
        Stopwatch::pause($measureKey);
        return $result;
    }

    /**
     * @param string $key
     * @param Closure|mixed $value
     * @param string|null $ttl
     * @return mixed
     */
    public function remember(string $key, $value, ?string $ttl = null)
    {
        $measureKey = sprintf('cacheservice-remember[%s]', $key);
        Counter::increase($measureKey);
        Stopwatch::start($measureKey);
        $result = parent::remember($key, $value, $ttl);
        Stopwatch::pause($measureKey);
        return $result;
    }
}
