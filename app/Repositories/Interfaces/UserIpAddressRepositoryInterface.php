<?php

namespace App\Repositories\Interfaces;

use App\Models\UserIpAddress;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method UserIpAddress             create(array $attributes)
 * @method UserIpAddress|null        find(int $id, array|string $columns = ['*'])
 * @method UserIpAddress             findOrFail(int $id, array|string $columns = ['*'])
 * @method UserIpAddress             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                      save(UserIpAddress $model)
 * @method bool                      update(UserIpAddress $model, array $attributes = [], array $options = [])
 * @method bool                      delete(UserIpAddress $model)
 * @method Collection<UserIpAddress> all()
 * @method bool                      exists(array $columns)
 */
interface UserIpAddressRepositoryInterface extends BaseRepositoryInterface
{
}
