<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\FloorUnion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method FloorUnion                  create(array<string, mixed> $attributes)
 * @method FloorUnion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method FloorUnion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method FloorUnion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                        save(FloorUnion $model)
 * @method bool                        update(FloorUnion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                        delete(FloorUnion $model)
 * @method Collection<int, FloorUnion> all()
 * @method bool                        exists(array<int, string> $columns)
 */
interface FloorUnionRepositoryInterface extends BaseRepositoryInterface
{
}
