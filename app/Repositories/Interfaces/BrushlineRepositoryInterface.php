<?php

namespace App\Repositories\Interfaces;

use App\Models\Brushline;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Brushline             create(array $attributes)
 * @method Brushline|null        find(int $id, array|string $columns = ['*'])
 * @method Brushline             findOrFail(int $id, array|string $columns = ['*'])
 * @method Brushline             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                  save(Brushline $model)
 * @method bool                  update(Brushline $model, array $attributes = [], array $options = [])
 * @method bool                  delete(Brushline $model)
 * @method Collection<Brushline> all()
 */
interface BrushlineRepositoryInterface extends BaseRepositoryInterface
{
}
