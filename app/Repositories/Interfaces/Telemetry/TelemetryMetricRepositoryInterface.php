<?php

namespace App\Repositories\Interfaces\Telemetry;

use App\Models\Telemetry\TelemetryMetric;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method TelemetryMetric                  create(array<string, mixed> $attributes)
 * @method TelemetryMetric|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method TelemetryMetric                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method TelemetryMetric                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                             save(TelemetryMetric $model)
 * @method bool                             update(TelemetryMetric $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                             delete(TelemetryMetric $model)
 * @method Collection<int, TelemetryMetric> all()
 * @method bool                             exists(array<int, string> $columns)
 */
interface TelemetryMetricRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function insertBatch(array $rows): bool;

    /**
     * Deletes at most $batchSize records recorded before $cutoff, returning the number of deleted rows.
     *
     * @param array<int, string> $excludedMeasurements Measurements that are never deleted, regardless of age
     */
    public function deleteRecordedBefore(Carbon $cutoff, int $batchSize, array $excludedMeasurements = []): int;
}
