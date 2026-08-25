<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use DB;

class MySqlStats extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getDataPoints(): array
    {
        $threadsConnected = (int)DB::select('SHOW STATUS WHERE `variable_name` = "Threads_connected"')[0]->Value;
        $maxThreads       = (int)DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value;

        return [
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_MYSQL, 'threads_connected_current', $threadsConnected),
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_MYSQL, 'threads_connected_max', $maxThreads),
        ];
    }
}
