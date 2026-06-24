<?php

namespace App\Repositories\Interfaces;

use App\Models\CacheModel;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CacheModel                  create(array<string, mixed> $attributes)
 * @method CacheModel|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CacheModel                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CacheModel                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                        save(CacheModel $model)
 * @method bool                        update(CacheModel $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                        delete(CacheModel $model)
 * @method Collection<int, CacheModel> all()
 * @method bool                        exists(array<int, string> $columns)
 */
interface CacheModelRepositoryInterface extends BaseRepositoryInterface
{
}
