<?php

namespace App\Repositories\Database\Telemetry;

use App\Models\Telemetry\TelemetryMetric;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Telemetry\TelemetryMetricRepositoryInterface;
use App\Service\Telemetry\Dtos\TelemetryCatalogEntry;
use App\Service\Telemetry\Dtos\TelemetrySeries;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

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

    public function getCatalog(Carbon $from): array
    {
        $rows = TelemetryMetric::query()
            ->select(['measurement', 'name', 'tag'])
            ->distinct()
            ->where('recorded_at', '>=', $from)
            ->orderBy('measurement')
            ->orderBy('name')
            ->orderBy('tag')
            ->get();

        return $rows
            ->map(static fn(TelemetryMetric $row): TelemetryCatalogEntry => new TelemetryCatalogEntry(
                $row->measurement,
                $row->name,
                $row->tag,
            ))
            ->all();
    }

    public function getSeries(string $measurement, ?string $name, Carbon $from, int $bucketSizeMinutes): array
    {
        $bucketFormat = $this->getBucketFormat($bucketSizeMinutes);

        $rows = $this->newBucketedQuery($measurement, $name, $from)
            ->selectRaw('`name`, `tag`, DATE_FORMAT(`recorded_at`, ?) AS `bucket`, AVG(`value`) AS `average`, MAX(`value`) AS `maximum`', [$bucketFormat])
            ->groupBy('name', 'tag', 'bucket')
            ->orderBy('name')
            ->orderBy('tag')
            ->orderBy('bucket')
            ->get();

        /** @var array<string, array{name: string, tag: string|null, buckets: array<int, string>, averages: array<int, float>, maximums: array<int, float>}> $seriesByKey */
        $seriesByKey = [];
        foreach ($rows as $row) {
            $key = sprintf('%s|%s', $row->name, $row->tag ?? '');

            $seriesByKey[$key] ??= [
                'name'     => (string)$row->name,
                'tag'      => $row->tag === null ? null : (string)$row->tag,
                'buckets'  => [],
                'averages' => [],
                'maximums' => [],
            ];

            $seriesByKey[$key]['buckets'][]  = (string)$row->bucket;
            $seriesByKey[$key]['averages'][] = (float)$row->average;
            $seriesByKey[$key]['maximums'][] = (float)$row->maximum;
        }

        return array_values(array_map(
            static fn(array $series): TelemetrySeries => new TelemetrySeries(
                $series['name'],
                $series['tag'],
                $series['buckets'],
                $series['averages'],
                $series['maximums'],
            ),
            $seriesByKey,
        ));
    }

    public function getFailureBuckets(string $measurement, ?string $name, Carbon $from, int $bucketSizeMinutes): array
    {
        $bucketFormat = $this->getBucketFormat($bucketSizeMinutes);

        return $this->newBucketedQuery($measurement, $name, $from)
            ->selectRaw('DISTINCT DATE_FORMAT(`recorded_at`, ?) AS `bucket`', [$bucketFormat])
            ->where('success', false)
            ->orderBy('bucket')
            ->pluck('bucket')
            ->map(static fn(mixed $bucket): string => (string)$bucket)
            ->all();
    }

    private function newBucketedQuery(string $measurement, ?string $name, Carbon $from): Builder
    {
        $query = TelemetryMetric::query()
            ->toBase()
            ->from('telemetry_metrics')
            ->where('measurement', $measurement)
            ->where('recorded_at', '>=', $from);

        if ($name !== null) {
            $query->where('name', $name);
        }

        return $query;
    }

    /**
     * MySQL truncates `recorded_at` to the bucket by formatting away everything below it, which keeps the
     * bucket label and the grouping key one and the same expression.
     */
    private function getBucketFormat(int $bucketSizeMinutes): string
    {
        return match ($bucketSizeMinutes) {
            1       => '%Y-%m-%d %H:%i',
            60      => '%Y-%m-%d %H:00',
            1440    => '%Y-%m-%d',
            default => throw new InvalidArgumentException(sprintf('Unsupported telemetry bucket size %d', $bucketSizeMinutes)),
        };
    }
}
