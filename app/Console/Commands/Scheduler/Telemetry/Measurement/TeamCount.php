<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Models\Team;
use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;

class TeamCount extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getDataPoints(): array
    {
        return [
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_TEAM_COUNT, 'all', Team::count()),
        ];
    }
}
