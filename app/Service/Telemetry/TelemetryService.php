<?php

namespace App\Service\Telemetry;

use App\Models\Telemetry\TelemetryMetric;
use App\Repositories\Interfaces\Telemetry\TelemetryMetricRepositoryInterface;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use App\Service\Telemetry\Logging\TelemetryServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Throwable;

class TelemetryService implements TelemetryServiceInterface
{
    public function __construct(
        private readonly TelemetryMetricRepositoryInterface $telemetryMetricRepository,
        private readonly TelemetryServiceLoggingInterface   $log,
    ) {
    }

    public function recordCommandRun(string $commandName, float $elapsedMs, bool $success, ?Carbon $startedAt = null): void
    {
        $this->recordDataPoints([
            new TelemetryDataPoint(
                TelemetryMetric::MEASUREMENT_SCHEDULER,
                $commandName,
                $elapsedMs,
                recordedAt: $startedAt,
                success: $success,
            ),
        ]);
    }

    public function recordDataPoints(array $dataPoints): void
    {
        if (count($dataPoints) === 0) {
            return;
        }

        try {
            $this->telemetryMetricRepository->insertBatch(
                array_map(static fn(TelemetryDataPoint $dataPoint) => $dataPoint->toRow(), $dataPoints),
            );
        } catch (Throwable $throwable) {
            $this->log->recordDataPointsFailed(count($dataPoints), $throwable->getMessage());
        }
    }
}
