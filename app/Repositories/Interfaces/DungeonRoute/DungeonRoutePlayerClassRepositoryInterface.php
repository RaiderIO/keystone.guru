<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoutePlayerClass create(array $attributes)
 * @method DungeonRoutePlayerClass find(int $id, array $columns = [])
 * @method DungeonRoutePlayerClass findOrFail(int $id, array $columns = [])
 * @method DungeonRoutePlayerClass findOrNew(int $id, array $columns = [])
 * @method bool save(DungeonRoutePlayerClass $model)
 * @method bool update(DungeonRoutePlayerClass $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRoutePlayerClass $model)
 * @method Collection<DungeonRoutePlayerClass> all()
 */
interface DungeonRoutePlayerClassRepositoryInterface extends BaseRepositoryInterface
{

}
