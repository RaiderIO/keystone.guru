<?php

namespace App\Service\Metric;

use App\Models\Metrics\Metric;
use App\Models\Metrics\MetricAggregation;
use App\Service\Cache\CacheServiceInterface;
use Artisan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MetricService implements MetricServiceInterface
{
    public function __construct(
        private readonly CacheServiceInterface $cacheService,
    ) {

    }

    public function storeMetric(?int $modelId, ?string $modelClass, int $category, string $tag, int $value): Metric
    {
        return Metric::create([
            'model_id'    => $modelId,
            'model_class' => $modelClass,
            'category'    => $category,
            'tag'         => $tag,
            'value'       => $value,
        ]);
    }

    public function storeMetricByModel(?Model $model, int $category, string $tag, int $value): Metric
    {
        return Metric::create([
            'model_id'    => $model?->id,
            'model_class' => $model !== null ? $model::class : null,
            'category'    => $category,
            'tag'         => $tag,
            'value'       => $value,
        ]);
    }

    public function storeMetricAsync(?int $modelId, ?string $modelClass, int $category, string $tag, int $value): void
    {
        $this->withLock('metrics:pending:lock', function () use ($modelId, $modelClass, $category, $tag, $value) {
            $key = 'metrics:pending';

            // Get current metrics list or initialize an empty array
            $pendingMetrics = $this->cacheService->get($key) ?? [];

            $now              = Carbon::now()->toDateTimeString();
            $pendingMetrics[] = [
                'model_id'    => $modelId,
                'model_class' => $modelClass,
                'category'    => $category,
                'tag'         => $tag,
                'value'       => $value,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            // Save the updated list back to the cache
            $this->cacheService->set($key, $pendingMetrics);
        });
    }

    public function flushPendingMetrics(?int $groupBySeconds = null): array
    {
        return $this->withLock('metrics:pending:lock', function () use ($groupBySeconds) {
            $key = 'metrics:pending';

            // Retrieve and clear the pending metrics
            $pendingMetrics = $this->cacheService->get($key) ?? [];
            $this->cacheService->set($key, []); // Reset the list

            return $groupBySeconds !== null ? $this->groupMetrics($pendingMetrics, $groupBySeconds) : $pendingMetrics;
        });
    }

    public function aggregateMetrics(): bool
    {
        $result = DB::insert("
            INSERT INTO metric_aggregations (model_id, model_class, category, tag, value, created_at, updated_at)
            SELECT model_id, model_class, category, tag, value, created_at, updated_at
            FROM (
                SELECT IF(model_id is null, -1, model_id) as model_id,
                       IF(model_class is null, '', model_class) as model_class,
                       category,
                       tag,
                       count(0) as value,
                       NOW() as created_at,
                       NOW() as updated_at
                FROM metrics
                GROUP BY model_id, model_class, category, tag
            ) as metrics
        ON DUPLICATE KEY UPDATE metric_aggregations.value = metrics.value, metric_aggregations.updated_at = metrics.updated_at;
        ");

        if ($result) {
            Artisan::call('modelCache:clear', ['--model' => MetricAggregation::class]);
        }

        return $result;
    }

    /**
     * @param array{array{model_id: int, model_class: string, category: string, tag: string, value: int, created_at: string, updated_at: string}} $pendingMetrics
     * @param int                                                                                                                                 $seconds
     * @return array
     */
    public function groupMetrics(array $pendingMetrics, int $seconds = 30): array
    {
        $groupedMetrics = [];

        foreach ($pendingMetrics as $metric) {
            $timestamp = Carbon::parse($metric['created_at'])->timestamp;

            $groupKey = sprintf('%d-%d-%s-%s-%s',
                $timestamp - ($timestamp % $seconds),
                $metric['model_id'],
                $metric['model_class'],
                $metric['category'],
                $metric['tag']
            );

            if (!isset($groupedMetrics[$groupKey])) {
                $groupedMetrics[$groupKey] = $metric;
            } else {
                $groupedMetrics[$groupKey]['value'] += $metric['value'];
            }
        }

        return array_values($groupedMetrics);
    }

    private function withLock(string $lockKey, callable $callback, int $ttl = 5): mixed
    {
        try {
            $lock = $this->cacheService->lock($lockKey, $ttl);

            // Execute the critical section
            return $callback();
        } finally {
            if (isset($lock)) {
                $lock->release();
            }
        }
    }
}
