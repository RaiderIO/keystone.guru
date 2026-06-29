<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method User                  create(array<string, mixed> $attributes)
 * @method User|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method User                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method User                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                  save(User $model)
 * @method bool                  update(User $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                  delete(User $model)
 * @method Collection<int, User> all()
 * @method bool                  exists(array<int, string> $columns)
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
}
