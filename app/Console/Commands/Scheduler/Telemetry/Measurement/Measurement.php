<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Service\Telemetry\Dtos\TelemetryDataPoint;

abstract class Measurement
{
    /**
     * @return TelemetryDataPoint[]
     */
    abstract public function getDataPoints(): array;
}
