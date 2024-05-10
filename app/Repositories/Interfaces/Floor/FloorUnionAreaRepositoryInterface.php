<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\FloorUnionArea;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method FloorUnionArea create(array $attributes)
 * @method FloorUnionArea find(int $id, array $columns = [])
 * @method FloorUnionArea findOrFail(int $id, array $columns = [])
 * @method FloorUnionArea findOrNew(int $id, array $columns = [])
 * @method bool save(FloorUnionArea $model)
 * @method bool update(FloorUnionArea $model, array $attributes = [], array $options = [])
 * @method bool delete(FloorUnionArea $model)
 * @method Collection<FloorUnionArea> all()
 */
interface FloorUnionAreaRepositoryInterface extends BaseRepositoryInterface
{

}
