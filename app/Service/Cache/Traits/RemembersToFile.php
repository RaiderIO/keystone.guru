<?php

namespace App\Service\Cache\Traits;

use Illuminate\Support\Facades\Cache;

trait RemembersToFile
{
    private function rememberLocal(string $key, int $ttl, \Closure $compute, bool $cacheEnabled = true): mixed
    {
        if (!$cacheEnabled || config('app.env') === 'local') {
            return $compute();
        }

        // Use a local cache store to avoid issues with file names
        // This is useful for temporary or development purposes
        // The key is prefixed with "local:" to avoid filename issues
        $localKey = "local:$key";                     // avoids filename issues

        return Cache::store('tmp_file')->remember($localKey, $ttl, $compute);
    }
}
