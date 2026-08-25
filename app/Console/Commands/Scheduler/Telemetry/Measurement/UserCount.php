<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Models\Telemetry\TelemetryMetric;
use App\Models\User;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;

class UserCount extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getDataPoints(): array
    {
        return [
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_USER_COUNT, 'all', User::count()),
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_USER_COUNT, 'keystoneguru', User::whereNull('oauth_id')->count()),
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_USER_COUNT, 'discord', User::where('oauth_id', 'LIKE', '%@discord')->count()),
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_USER_COUNT, 'google', User::where('oauth_id', 'LIKE', '%@google')->count()),
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_USER_COUNT, 'battlenet', User::where('oauth_id', 'LIKE', '%@battlenet')->count()),
        ];
    }
}
