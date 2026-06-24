<?php

namespace App\Repositories\Interfaces\Metrics;

use App\Models\Metrics\Metric;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Metric                  create(array<string, mixed> $attributes)
 * @method Metric|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Metric                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Metric                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                    save(Metric $model)
 * @method bool                    update(Metric $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                    delete(Metric $model)
 * @method Collection<int, Metric> all()
 * @method bool                    exists(array<int, string> $columns)
 */
interface MetricRepositoryInterface extends BaseRepositoryInterface
{
}
