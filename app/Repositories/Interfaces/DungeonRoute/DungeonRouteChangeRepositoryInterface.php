<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteChange;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteChange                  create(array<string, mixed> $attributes)
 * @method DungeonRouteChange|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteChange                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteChange                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                save(DungeonRouteChange $model)
 * @method bool                                update(DungeonRouteChange $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                delete(DungeonRouteChange $model)
 * @method Collection<int, DungeonRouteChange> all()
 * @method bool                                exists(array<int, string> $columns)
 */
interface DungeonRouteChangeRepositoryInterface extends BaseRepositoryInterface
{
}
