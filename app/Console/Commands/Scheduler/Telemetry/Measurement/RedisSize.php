<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use Illuminate\Support\Facades\Redis;

class RedisSize extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getDataPoints(): array
    {
        // Several of the configured connections (default/model_cache/cache/session) may point at the
        // same logical Redis database - dedupe by database index so each one is only sampled once,
        // instead of recording identical DBSIZE values under multiple tags.
        $connectionNameByDatabase = [];
        foreach (config('database.redis') as $connectionName => $connectionConfig) {
            if (!is_array($connectionConfig) || !isset($connectionConfig['database'])) {
                continue;
            }

            $connectionNameByDatabase[(string)$connectionConfig['database']] ??= $connectionName;
        }

        $result = [];
        foreach ($connectionNameByDatabase as $database => $connectionName) {
            $result[] = new TelemetryDataPoint(
                TelemetryMetric::MEASUREMENT_REDIS,
                'keys',
                (float)Redis::connection($connectionName)->dbsize(),
                sprintf('db%s', $database),
            );
        }

        return $result;
    }
}
