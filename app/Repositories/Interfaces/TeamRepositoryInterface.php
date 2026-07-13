<?php

namespace App\Repositories\Interfaces;

use App\Models\Team;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Team                  create(array<string, mixed> $attributes)
 * @method Team|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Team                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Team                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                  save(Team $model)
 * @method bool                  update(Team $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                  delete(Team $model)
 * @method Collection<int, Team> all()
 * @method bool                  exists(array<int, string> $columns)
 */
interface TeamRepositoryInterface extends BaseRepositoryInterface
{
}
