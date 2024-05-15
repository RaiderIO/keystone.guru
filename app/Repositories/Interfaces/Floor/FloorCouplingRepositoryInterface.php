<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\FloorCoupling;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method FloorCoupling create(array $attributes)
 * @method FloorCoupling find(int $id, array $columns = [])
 * @method FloorCoupling findOrFail(int $id, array $columns = [])
 * @method FloorCoupling findOrNew(int $id, array $columns = [])
 * @method bool save(FloorCoupling $model)
 * @method bool update(FloorCoupling $model, array $attributes = [], array $options = [])
 * @method bool delete(FloorCoupling $model)
 * @method Collection<FloorCoupling> all()
 */
interface FloorCouplingRepositoryInterface extends BaseRepositoryInterface
{

}
