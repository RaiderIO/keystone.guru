<?php

namespace App\Service\Telemetry\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class TelemetryServiceLogging extends StructuredLogging implements TelemetryServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function recordDataPointsFailed(int $dataPointCount, string $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
