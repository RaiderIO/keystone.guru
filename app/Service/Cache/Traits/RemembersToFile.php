<?php

namespace App\Service\Cache\Traits;

use Illuminate\Support\Facades\Cache;

trait RemembersToFile
{
    private function rememberLocal(string $key, int $ttl, \Closure $compute)
    {
        $localKey = "local:$key";                     // avoids filename issues

        return Cache::store('tmp_file')->remember($localKey, $ttl, $compute);
    }
}
