<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoute create(array $attributes)
 * @method DungeonRoute find(int $id, array $columns = [])
 * @method DungeonRoute findOrFail(int $id, array $columns = [])
 * @method DungeonRoute findOrNew(int $id, array $columns = [])
 * @method bool save(DungeonRoute $model)
 * @method bool update(DungeonRoute $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRoute $model)
 * @method Collection<DungeonRoute> all()
 */
interface DungeonRouteRepositoryInterface extends BaseRepositoryInterface
{

}
