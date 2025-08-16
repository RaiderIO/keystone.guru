<?php

namespace App\Service\Cache;

use App\Logic\Utils\Counter;
use App\Logic\Utils\Stopwatch;
use Psr\SimpleCache\InvalidArgumentException;

class DevCacheService extends CacheService
{
    public function get(string $key): mixed
    {
        $result = parent::get($key);
        Counter::increase(sprintf('cacheservice[%s]:%s', $key, $result === null ? 'miss' : 'hit'));

        return $result;
    }

    /**
     * @param Closure|mixed $value
     *
     * @throws InvalidArgumentException
     */
    public function rememberWhen(bool $condition, string $key, mixed $value, mixed $ttl = null): mixed
    {
        $measureKey = sprintf('cacheservice-rememberwhen[%s]:%s', $key, $condition ? 'pass' : 'fail');
        Counter::increase($measureKey);
        Stopwatch::start($measureKey);
        $result = parent::rememberWhen($condition, $key, $value, $ttl);
        Stopwatch::pause($measureKey);

        return $result;
    }

    /**
     * @param Closure|mixed $value
     */
    public function remember(string $key, mixed $value, mixed $ttl = null): mixed
    {
        $measureKey = sprintf('cacheservice-remember[%s]', $key);
        Counter::increase($measureKey);
        Stopwatch::start($measureKey);
        $result = parent::remember($key, $value, $ttl);
        Stopwatch::pause($measureKey);

        return $result;
    }
}
