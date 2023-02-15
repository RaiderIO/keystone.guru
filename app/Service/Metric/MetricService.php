<?php

namespace App\Service\Metric;

use App\Models\Metrics\Metric;
use Illuminate\Database\Eloquent\Model;

class MetricService implements MetricServiceInterface
{
    /**
     * @param int|null $modelId
     * @param string|null $modelClass
     * @param int $category
     * @param string $tag
     * @param int $value
     * @return Metric
     */
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

    /**
     * @param Model|null $model
     * @param int $category
     * @param string $tag
     * @param int $value
     * @return Metric
     */
    public function storeMetricByModel(Model $model, int $category, string $tag, int $value): Metric
    {
        return Metric::create([
            'model_id'    => $model->id,
            'model_class' => get_class($model),
            'category'    => $category,
            'tag'         => $tag,
            'value'       => $value,
        ]);
    }
}
