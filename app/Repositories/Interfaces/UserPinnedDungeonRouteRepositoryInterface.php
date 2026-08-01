<?php

namespace App\Repositories\Interfaces;

use App\Models\UserPinnedDungeonRoute;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method UserPinnedDungeonRoute                  create(array<string, mixed> $attributes)
 * @method UserPinnedDungeonRoute|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method UserPinnedDungeonRoute                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method UserPinnedDungeonRoute                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(UserPinnedDungeonRoute $model)
 * @method bool                                    update(UserPinnedDungeonRoute $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(UserPinnedDungeonRoute $model)
 * @method Collection<int, UserPinnedDungeonRoute> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface UserPinnedDungeonRouteRepositoryInterface extends BaseRepositoryInterface
{
}
