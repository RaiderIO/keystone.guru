<?php

namespace App\Repositories\Interfaces;

use App\Models\Brushline;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Brushline                  create(array<string, mixed> $attributes)
 * @method Brushline|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Brushline                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Brushline                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                       save(Brushline $model)
 * @method bool                       update(Brushline $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                       delete(Brushline $model)
 * @method Collection<int, Brushline> all()
 * @method bool                       exists(array<int, string> $columns)
 */
interface BrushlineRepositoryInterface extends BaseRepositoryInterface
{
}
