<?php

namespace App\Repositories\Database\Telemetry;

use App\Models\Telemetry\TelemetryMetric;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Telemetry\TelemetryMetricRepositoryInterface;
use Illuminate\Support\Carbon;

class TelemetryMetricRepository extends DatabaseRepository implements TelemetryMetricRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(TelemetryMetric::class);
    }

    public function insertBatch(array $rows): bool
    {
        return TelemetryMetric::query()->insert($rows);
    }

    public function deleteRecordedBefore(Carbon $cutoff, int $batchSize, array $excludedMeasurements = []): int
    {
        return TelemetryMetric::query()
            ->where('recorded_at', '<', $cutoff)
            ->whereNotIn('measurement', $excludedMeasurements)
            ->limit($batchSize)
            ->delete();
    }
}
