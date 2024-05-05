<?php

namespace App\Repositories\Interfaces\Metrics;

use App\Models\Metrics\Metric;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Metric create(array $attributes)
 * @method Metric find(int $id, array $columns = [])
 * @method Metric findOrFail(int $id, array $columns = [])
 * @method Metric findOrNew(int $id, array $columns = [])
 * @method bool save(Metric $model)
 * @method bool update(Metric $model, array $attributes = [], array $options = [])
 * @method bool delete(Metric $model)
 * @method Collection<Metric> all()
 */
interface MetricRepositoryInterface extends BaseRepositoryInterface
{

}
