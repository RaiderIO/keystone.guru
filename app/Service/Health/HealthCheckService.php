<?php

namespace App\Service\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthCheckService implements HealthCheckServiceInterface
{
    public function isDatabaseReachable(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            return true;
        } catch (Throwable $e) {
            Log::error('Status check failed: database', ['exception' => $e]);

            return false;
        }
    }

    public function isRedisReachable(): bool
    {
        try {
            $pong = Redis::connection()->client()->ping();
        } catch (Throwable $e) {
            Log::error('Status check failed: redis', ['exception' => $e]);

            return false;
        }

        // phpredis returns true (or the string "PONG"); predis returns a Status object
        return $pong === true || $pong === 'PONG' || (is_object($pong) && method_exists($pong, 'getPayload') && $pong->getPayload() === 'PONG');
    }
}
