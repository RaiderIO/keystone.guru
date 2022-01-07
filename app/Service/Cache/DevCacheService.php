<?php


namespace App\Service\Cache;

use App\Logic\Utils\Counter;

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
        Counter::increase(sprintf('cacheservice-rememberwhen[%s]:%s', $key, $condition ? 'hit' : 'miss'));
        return parent::rememberWhen($condition, $key, $value, $ttl);
    }
}
