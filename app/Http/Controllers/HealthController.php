<?php

namespace App\Http\Controllers;

use App\Service\Health\HealthCheckServiceInterface;
use Illuminate\Http\JsonResponse;
use Teapot\StatusCode;

class HealthController extends Controller
{
    private const string CHECK_OK   = 'ok';
    private const string CHECK_FAIL = 'fail';

    public function check(HealthCheckServiceInterface $healthCheckService): JsonResponse
    {
        $checks = [
            'database' => $healthCheckService->isDatabaseReachable() ? self::CHECK_OK : self::CHECK_FAIL,
            'redis'    => $healthCheckService->isRedisReachable() ? self::CHECK_OK : self::CHECK_FAIL,
        ];

        $healthy = !in_array(self::CHECK_FAIL, $checks, true);

        return response()->json([
            'status' => $healthy ? self::CHECK_OK : self::CHECK_FAIL,
            'checks' => $checks,
        ], $healthy ? StatusCode::OK : StatusCode::SERVICE_UNAVAILABLE);
    }
}
