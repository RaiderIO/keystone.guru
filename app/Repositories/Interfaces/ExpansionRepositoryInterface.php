<?php

namespace App\Repositories\Interfaces;

use App\Models\Expansion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Expansion                  create(array<string, mixed> $attributes)
 * @method Expansion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Expansion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Expansion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                       save(Expansion $model)
 * @method bool                       update(Expansion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                       delete(Expansion $model)
 * @method Collection<int, Expansion> all()
 * @method bool                       exists(array<int, string> $columns)
 */
interface ExpansionRepositoryInterface extends BaseRepositoryInterface
{
}
