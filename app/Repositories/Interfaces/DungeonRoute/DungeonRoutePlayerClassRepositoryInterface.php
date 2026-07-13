<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoutePlayerClass                  create(array<string, mixed> $attributes)
 * @method DungeonRoutePlayerClass|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRoutePlayerClass                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRoutePlayerClass                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                     save(DungeonRoutePlayerClass $model)
 * @method bool                                     update(DungeonRoutePlayerClass $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                     delete(DungeonRoutePlayerClass $model)
 * @method Collection<int, DungeonRoutePlayerClass> all()
 * @method bool                                     exists(array<int, string> $columns)
 */
interface DungeonRoutePlayerClassRepositoryInterface extends BaseRepositoryInterface
{
}
