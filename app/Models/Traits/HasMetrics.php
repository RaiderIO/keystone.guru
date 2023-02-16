<?php

namespace App\Models\Traits;

use App\Models\Metrics\Metric;
use App\Models\Metrics\MetricAggregation;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \Eloquent
 */
trait HasMetrics
{
    /**
     * @param int $category
     * @param string $tag
     * @return int
     */
    public function metric(int $category, string $tag): int
    {
        return (int)$this->hasMany(Metric::class, 'model_id')
            ->where('model_class', get_class($this))
            ->where('category', $category)
            ->where('tag', $tag)
            ->sum('value');
    }

    /**
     * @param int $category
     * @param string $tag
     * @return int
     */
    public function metricAggregated(int $category, string $tag): int
    {
        return (int)$this->hasOne(MetricAggregation::class, 'model_id')
            ->where('model_class', get_class($this))
            ->where('category', $category)
            ->where('tag', $tag)
            ->get('value');
    }

    /**
     * @return HasMany
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class, 'model_id')
            ->where('model_class', get_class($this));
    }

    /**
     * @return HasMany
     */
    public function metricAggregations(): HasMany
    {
        return $this->hasMany(MetricAggregation::class, 'model_id')
            ->where('model_class', get_class($this));
    }
}
