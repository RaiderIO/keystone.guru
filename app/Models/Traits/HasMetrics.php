<?php

namespace App\Models\Traits;

use App\Models\Metrics\Metric;

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
}
