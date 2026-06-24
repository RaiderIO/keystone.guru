<?php

namespace App\Repositories\Interfaces;

use App\Models\Path;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Path                  create(array<string, mixed> $attributes)
 * @method Path|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Path                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Path                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                  save(Path $model)
 * @method bool                  update(Path $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                  delete(Path $model)
 * @method Collection<int, Path> all()
 * @method bool                  exists(array<int, string> $columns)
 */
interface PathRepositoryInterface extends BaseRepositoryInterface
{
}
