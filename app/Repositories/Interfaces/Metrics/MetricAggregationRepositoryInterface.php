<?php

namespace App\Repositories\Interfaces\Metrics;

use App\Models\Metrics\MetricAggregation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MetricAggregation                  create(array<string, mixed> $attributes)
 * @method MetricAggregation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MetricAggregation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MetricAggregation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                               save(MetricAggregation $model)
 * @method bool                               update(MetricAggregation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                               delete(MetricAggregation $model)
 * @method Collection<int, MetricAggregation> all()
 * @method bool                               exists(array<int, string> $columns)
 */
interface MetricAggregationRepositoryInterface extends BaseRepositoryInterface
{
}
