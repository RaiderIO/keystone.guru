<?php


namespace App\Service\Cache;

use App\Logic\Utils\Counter;
use Illuminate\Support\Facades\Cache;

class DevCacheService extends CacheService
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $result = Cache::get($key);
        Counter::increase(sprintf('cacheservice[%s]:%s', $key, $result === null ? 'miss' : 'hit'));
        return $result;
    }
}
