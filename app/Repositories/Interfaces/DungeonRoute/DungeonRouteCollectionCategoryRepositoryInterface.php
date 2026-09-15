<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteCollectionCategory                  create(array<string, mixed> $attributes)
 * @method DungeonRouteCollectionCategory|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollectionCategory                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollectionCategory                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                            save(DungeonRouteCollectionCategory $model)
 * @method bool                                            update(DungeonRouteCollectionCategory $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                            delete(DungeonRouteCollectionCategory $model)
 * @method Collection<int, DungeonRouteCollectionCategory> all()
 * @method bool                                            exists(array<int, string> $columns)
 */
interface DungeonRouteCollectionCategoryRepositoryInterface extends BaseRepositoryInterface
{
}
