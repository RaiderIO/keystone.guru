<?php

namespace App\Repositories\Interfaces;

use App\Models\File;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method File                  create(array<string, mixed> $attributes)
 * @method File|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method File                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method File                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                  save(File $model)
 * @method bool                  update(File $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                  delete(File $model)
 * @method Collection<int, File> all()
 * @method bool                  exists(array<int, string> $columns)
 */
interface FileRepositoryInterface extends BaseRepositoryInterface
{
}
