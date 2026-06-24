<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\FloorCoupling;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method FloorCoupling                  create(array<string, mixed> $attributes)
 * @method FloorCoupling|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method FloorCoupling                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method FloorCoupling                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(FloorCoupling $model)
 * @method bool                           update(FloorCoupling $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(FloorCoupling $model)
 * @method Collection<int, FloorCoupling> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface FloorCouplingRepositoryInterface extends BaseRepositoryInterface
{
}
