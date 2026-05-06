<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteFavorite             create(array $attributes)
 * @method DungeonRouteFavorite|null        find(int $id, array|string $columns = ['*'])
 * @method DungeonRouteFavorite             findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonRouteFavorite             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                             save(DungeonRouteFavorite $model)
 * @method bool                             update(DungeonRouteFavorite $model, array $attributes = [], array $options = [])
 * @method bool                             delete(DungeonRouteFavorite $model)
 * @method Collection<DungeonRouteFavorite> all()
 */
interface DungeonRouteFavoriteRepositoryInterface extends BaseRepositoryInterface
{
}
