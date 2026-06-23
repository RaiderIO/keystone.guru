<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\FloorUnionArea;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method FloorUnionArea                  create(array<string, mixed> $attributes)
 * @method FloorUnionArea|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method FloorUnionArea                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method FloorUnionArea                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(FloorUnionArea $model)
 * @method bool                            update(FloorUnionArea $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(FloorUnionArea $model)
 * @method Collection<int, FloorUnionArea> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface FloorUnionAreaRepositoryInterface extends BaseRepositoryInterface
{
}
