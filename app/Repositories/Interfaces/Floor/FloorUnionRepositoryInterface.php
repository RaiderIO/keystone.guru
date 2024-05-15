<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\FloorUnion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method FloorUnion create(array $attributes)
 * @method FloorUnion|null find(int $id, array|string $columns = ['*'])
 * @method FloorUnion findOrFail(int $id, array|string $columns = ['*'])
 * @method FloorUnion findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(FloorUnion $model)
 * @method bool update(FloorUnion $model, array $attributes = [], array $options = [])
 * @method bool delete(FloorUnion $model)
 * @method Collection<FloorUnion> all()
 */
interface FloorUnionRepositoryInterface extends BaseRepositoryInterface
{

}
