<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\Floor;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Floor create(array $attributes)
 * @method Floor find(int $id, array $columns = [])
 * @method Floor findOrFail(int $id, array $columns = [])
 * @method Floor findOrNew(int $id, array $columns = [])
 * @method bool save(Floor $model)
 * @method bool update(Floor $model, array $attributes = [], array $options = [])
 * @method bool delete(Floor $model)
 * @method Collection<Floor> all()
 */
interface FloorRepositoryInterface extends BaseRepositoryInterface
{

}
