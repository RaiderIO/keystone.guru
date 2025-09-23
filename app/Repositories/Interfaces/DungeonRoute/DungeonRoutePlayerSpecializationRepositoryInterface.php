<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoutePlayerSpecialization             create(array $attributes)
 * @method DungeonRoutePlayerSpecialization|null        find(int $id, array|string $columns = ['*'])
 * @method DungeonRoutePlayerSpecialization             findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonRoutePlayerSpecialization             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                                         save(DungeonRoutePlayerSpecialization $model)
 * @method bool                                         update(DungeonRoutePlayerSpecialization $model, array $attributes = [], array $options = [])
 * @method bool                                         delete(DungeonRoutePlayerSpecialization $model)
 * @method Collection<DungeonRoutePlayerSpecialization> all()
 */
interface DungeonRoutePlayerSpecializationRepositoryInterface extends BaseRepositoryInterface
{
}
