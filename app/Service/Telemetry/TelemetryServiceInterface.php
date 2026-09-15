<?php

namespace App\Service\Telemetry;

use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use Illuminate\Support\Carbon;

interface TelemetryServiceInterface
{
    /**
     * Records how long a scheduled command took to run. Never throws - telemetry may not fail the command it measures.
     */
    public function recordCommandRun(string $commandName, float $elapsedMs, bool $success, ?Carbon $startedAt = null): void;

    /**
     * Persists a batch of data points in a single insert. Never throws - telemetry may not fail the operation it measures.
     *
     * @param TelemetryDataPoint[] $dataPoints
     */
    public function recordDataPoints(array $dataPoints): void;
}
