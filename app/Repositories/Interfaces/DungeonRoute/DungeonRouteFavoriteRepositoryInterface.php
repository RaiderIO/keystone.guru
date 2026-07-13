<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteFavorite                  create(array<string, mixed> $attributes)
 * @method DungeonRouteFavorite|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteFavorite                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteFavorite                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                  save(DungeonRouteFavorite $model)
 * @method bool                                  update(DungeonRouteFavorite $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                  delete(DungeonRouteFavorite $model)
 * @method Collection<int, DungeonRouteFavorite> all()
 * @method bool                                  exists(array<int, string> $columns)
 */
interface DungeonRouteFavoriteRepositoryInterface extends BaseRepositoryInterface
{
}
