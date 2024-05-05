<?php

namespace App\Repositories\Interfaces;

use App\Models\Polyline;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Polyline create(array $attributes)
 * @method Polyline find(int $id, array $columns = [])
 * @method Polyline findOrFail(int $id, array $columns = [])
 * @method Polyline findOrNew(int $id, array $columns = [])
 * @method bool save(Polyline $model)
 * @method bool update(Polyline $model, array $attributes = [], array $options = [])
 * @method bool delete(Polyline $model)
 * @method Collection<Polyline> all()
 */
interface PolylineRepositoryInterface extends BaseRepositoryInterface
{

}
