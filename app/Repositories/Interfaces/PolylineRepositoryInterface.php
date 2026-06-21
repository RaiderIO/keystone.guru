<?php

namespace App\Repositories\Interfaces;

use App\Models\Polyline;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Polyline                  create(array<string, mixed> $attributes)
 * @method Polyline|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Polyline                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Polyline                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                      save(Polyline $model)
 * @method bool                      update(Polyline $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                      delete(Polyline $model)
 * @method Collection<int, Polyline> all()
 * @method bool                      exists(array<int, string> $columns)
 */
interface PolylineRepositoryInterface extends BaseRepositoryInterface
{
}
