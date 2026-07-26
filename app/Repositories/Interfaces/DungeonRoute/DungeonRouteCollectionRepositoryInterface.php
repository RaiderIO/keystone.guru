<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteCollection                  create(array<string, mixed> $attributes)
 * @method DungeonRouteCollection|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollection                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteCollection                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(DungeonRouteCollection $model)
 * @method bool                                    update(DungeonRouteCollection $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(DungeonRouteCollection $model)
 * @method Collection<int, DungeonRouteCollection> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface DungeonRouteCollectionRepositoryInterface extends BaseRepositoryInterface
{
}
