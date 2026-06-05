<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteChange;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteChange             create(array $attributes)
 * @method DungeonRouteChange|null        find(int $id, array|string $columns = ['*'])
 * @method DungeonRouteChange             findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonRouteChange             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                           save(DungeonRouteChange $model)
 * @method bool                           update(DungeonRouteChange $model, array $attributes = [], array $options = [])
 * @method bool                           delete(DungeonRouteChange $model)
 * @method Collection<DungeonRouteChange> all()
 * @method bool                           exists(array $columns)
 */
interface DungeonRouteChangeRepositoryInterface extends BaseRepositoryInterface
{
}
