<?php

namespace App\Models\Traits;

use App\Models\Metrics\Metric;
use App\Models\Metrics\MetricAggregation;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property Collection|Metric[] $metrics
 * @property Collection|MetricAggregation[] $metricAggregations
 *
 * @mixin Eloquent
 */
trait HasMetrics
{
    public function metric(int $category, string $tag): int
    {
        return (int) $this->hasMany(Metric::class, 'model_id')
            ->where('model_class', $this::class)
            ->where('category', $category)
            ->where('tag', $tag)
            ->sum('value');
    }

    public function metricAggregated(int $category, string $tag): int
    {
        /** @var MetricAggregation $metricAggregation */
        $metricAggregation = $this->hasOne(MetricAggregation::class, 'model_id')
            ->where('model_class', $this::class)
            ->where('category', $category)
            ->where('tag', $tag)
            ->get()
            ->first();

        return $metricAggregation->value;
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class, 'model_id')
            ->where('model_class', $this::class);
    }

    public function metricAggregations(): HasMany
    {
        return $this->hasMany(MetricAggregation::class, 'model_id')
            ->where('model_class', $this::class);
    }
}
