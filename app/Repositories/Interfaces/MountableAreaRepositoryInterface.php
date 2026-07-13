<?php

namespace App\Repositories\Interfaces;

use App\Models\MountableArea;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MountableArea                  create(array<string, mixed> $attributes)
 * @method MountableArea|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MountableArea                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MountableArea                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(MountableArea $model)
 * @method bool                           update(MountableArea $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(MountableArea $model)
 * @method Collection<int, MountableArea> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface MountableAreaRepositoryInterface extends BaseRepositoryInterface
{
}
