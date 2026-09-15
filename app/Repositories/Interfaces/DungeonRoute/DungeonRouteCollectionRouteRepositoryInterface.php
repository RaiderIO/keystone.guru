<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteCollectionRoute                  create(array<string, mixed> $attributes)
 * @method DungeonRouteCollectionRoute|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollectionRoute                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollectionRoute                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                         save(DungeonRouteCollectionRoute $model)
 * @method bool                                         update(DungeonRouteCollectionRoute $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                         delete(DungeonRouteCollectionRoute $model)
 * @method Collection<int, DungeonRouteCollectionRoute> all()
 * @method bool                                         exists(array<int, string> $columns)
 */
interface DungeonRouteCollectionRouteRepositoryInterface extends BaseRepositoryInterface
{
}
