<?php

namespace App\Service\Metric;

use App\Models\Metrics\Metric;
use Illuminate\Database\Eloquent\Model;

interface MetricServiceInterface
{
    public function storeMetric(?int $modelId, ?string $modelClass, int $category, string $tag, int $value): Metric;

    public function storeMetricByModel(?Model $model, int $category, string $tag, int $value): Metric;

    public function storeMetricAsync(?int $modelId, ?string $modelClass, int $category, string $tag, int $value): void;

    public function flushPendingMetrics(?int $groupBySeconds = null): array;

    public function aggregateMetrics(): bool;

    public function groupMetrics(array $pendingMetrics, int $seconds = 30): array;
}
