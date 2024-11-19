<?php

namespace App\Overrides;

use Illuminate\Cache\RateLimiter as BaseRateLimiter;

class CustomRateLimiter extends BaseRateLimiter
{
    public function __construct($cache)
    {
        parent::__construct($cache);
    }

    public function cleanRateLimiterKey($key): string
    {
        // Add a custom prefix specifically for rate limiter keys
        $prefix = 'rate-limiter:';

        return $prefix . parent::cleanRateLimiterKey($key);
    }
}
