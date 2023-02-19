<?php

namespace App\Service\Metric;

use App\Models\Metrics\Metric;
use App\Models\Metrics\MetricAggregation;
use Artisan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /**
     * @return void
     */
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
}
