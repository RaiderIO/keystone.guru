<?php

namespace App\Repositories\Interfaces\Metrics;

use App\Models\Metrics\MetricAggregation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MetricAggregation             create(array $attributes)
 * @method MetricAggregation|null        find(int $id, array|string $columns = ['*'])
 * @method MetricAggregation             findOrFail(int $id, array|string $columns = ['*'])
 * @method MetricAggregation             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                          save(MetricAggregation $model)
 * @method bool                          update(MetricAggregation $model, array $attributes = [], array $options = [])
 * @method bool                          delete(MetricAggregation $model)
 * @method Collection<MetricAggregation> all()
 */
interface MetricAggregationRepositoryInterface extends BaseRepositoryInterface
{
}
