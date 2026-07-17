<?php

namespace App\Repositories\Interfaces;

use App\Models\BannedIpAddress;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method BannedIpAddress                  create(array<string, mixed> $attributes)
 * @method BannedIpAddress|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method BannedIpAddress                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method BannedIpAddress                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                             save(BannedIpAddress $model)
 * @method bool                             update(BannedIpAddress $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                             delete(BannedIpAddress $model)
 * @method Collection<int, BannedIpAddress> all()
 * @method bool                             exists(array<int, string> $columns)
 */
interface BannedIpAddressRepositoryInterface extends BaseRepositoryInterface
{
}
