<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoutePlayerSpecialization create(array $attributes)
 * @method DungeonRoutePlayerSpecialization find(int $id, array $columns = [])
 * @method DungeonRoutePlayerSpecialization findOrFail(int $id, array $columns = [])
 * @method DungeonRoutePlayerSpecialization findOrNew(int $id, array $columns = [])
 * @method bool save(DungeonRoutePlayerSpecialization $model)
 * @method bool update(DungeonRoutePlayerSpecialization $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRoutePlayerSpecialization $model)
 * @method Collection<DungeonRoutePlayerSpecialization> all()
 */
interface DungeonRoutePlayerSpecializationRepositoryInterface extends BaseRepositoryInterface
{

}
