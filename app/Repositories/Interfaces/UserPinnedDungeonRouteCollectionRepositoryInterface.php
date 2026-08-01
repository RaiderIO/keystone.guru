<?php

namespace App\Repositories\Interfaces;

use App\Models\UserPinnedDungeonRouteCollection;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method UserPinnedDungeonRouteCollection                  create(array<string, mixed> $attributes)
 * @method UserPinnedDungeonRouteCollection|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method UserPinnedDungeonRouteCollection                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method UserPinnedDungeonRouteCollection                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                              save(UserPinnedDungeonRouteCollection $model)
 * @method bool                                              update(UserPinnedDungeonRouteCollection $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                              delete(UserPinnedDungeonRouteCollection $model)
 * @method Collection<int, UserPinnedDungeonRouteCollection> all()
 * @method bool                                              exists(array<int, string> $columns)
 */
interface UserPinnedDungeonRouteCollectionRepositoryInterface extends BaseRepositoryInterface
{
}
