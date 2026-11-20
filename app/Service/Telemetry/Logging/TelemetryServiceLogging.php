<?php

namespace App\Service\Telemetry\Logging;

use App\Logging\StructuredLogging;

class TelemetryServiceLogging extends StructuredLogging implements TelemetryServiceLoggingInterface
{
    public function recordDataPointsFailed(int $dataPointCount, string $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
