<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteAttribute                  create(array<string, mixed> $attributes)
 * @method DungeonRouteAttribute|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteAttribute                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteAttribute                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                   save(DungeonRouteAttribute $model)
 * @method bool                                   update(DungeonRouteAttribute $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                   delete(DungeonRouteAttribute $model)
 * @method Collection<int, DungeonRouteAttribute> all()
 * @method bool                                   exists(array<int, string> $columns)
 */
interface DungeonRouteAttributeRepositoryInterface extends BaseRepositoryInterface
{
}
