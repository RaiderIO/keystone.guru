<?php

namespace App\Repositories\Interfaces\Tags;

use App\Models\Tags\TagCategory;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method TagCategory                  create(array<string, mixed> $attributes)
 * @method TagCategory|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method TagCategory                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method TagCategory                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                         save(TagCategory $model)
 * @method bool                         update(TagCategory $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                         delete(TagCategory $model)
 * @method Collection<int, TagCategory> all()
 * @method bool                         exists(array<int, string> $columns)
 */
interface TagCategoryRepositoryInterface extends BaseRepositoryInterface
{
}
