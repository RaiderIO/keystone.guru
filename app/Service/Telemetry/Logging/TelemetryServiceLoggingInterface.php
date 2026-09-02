<?php

namespace App\Service\Telemetry\Logging;

interface TelemetryServiceLoggingInterface
{
    public function recordDataPointsFailed(int $dataPointCount, string $exception): void;
}
