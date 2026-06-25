<?php

namespace App\Repositories\Interfaces;

use App\Models\UserIpAddress;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method UserIpAddress                  create(array<string, mixed> $attributes)
 * @method UserIpAddress|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method UserIpAddress                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method UserIpAddress                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(UserIpAddress $model)
 * @method bool                           update(UserIpAddress $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(UserIpAddress $model)
 * @method Collection<int, UserIpAddress> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface UserIpAddressRepositoryInterface extends BaseRepositoryInterface
{
}
