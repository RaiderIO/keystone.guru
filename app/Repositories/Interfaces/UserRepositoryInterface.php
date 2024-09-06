<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method User create(array $attributes)
 * @method User|null find(int $id, array|string $columns = ['*'])
 * @method User findOrFail(int $id, array|string $columns = ['*'])
 * @method User findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(User $model)
 * @method bool update(User $model, array $attributes = [], array $options = [])
 * @method bool delete(User $model)
 * @method Collection<User> all()
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{

}
