<?php

namespace App\Repositories\Interfaces\Telemetry;

use App\Models\Telemetry\TelemetryMetric;
use App\Repositories\BaseRepositoryInterface;
use App\Service\Telemetry\Dtos\TelemetryCatalogEntry;
use App\Service\Telemetry\Dtos\TelemetrySeries;
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

    /**
     * Every (measurement, name, tag) combination that recorded at least one data point since $from, so the
     * dashboard can build its chart list from the data instead of a hardcoded list of measurements.
     *
     * @return array<int, TelemetryCatalogEntry>
     */
    public function getCatalog(Carbon $from): array;

    /**
     * The time series of a measurement since $from, bucketed to $bucketSizeMinutes and split per (name, tag).
     *
     * Both the average and the maximum of each bucket are returned: an average alone hides the spikes that make
     * a duration graph worth looking at.
     *
     * @param string|null $name Limits the result to a single name, e.g. one scheduled command
     *
     * @return array<int, TelemetrySeries>
     */
    public function getSeries(string $measurement, ?string $name, Carbon $from, int $bucketSizeMinutes): array;

    /**
     * The bucket labels in which at least one run of $name failed, so they can be marked on its chart.
     *
     * @return array<int, string>
     */
    public function getFailureBuckets(string $measurement, ?string $name, Carbon $from, int $bucketSizeMinutes): array;
}
