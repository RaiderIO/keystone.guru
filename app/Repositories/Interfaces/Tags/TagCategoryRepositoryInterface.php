<?php

namespace App\Repositories\Interfaces\Tags;

use App\Models\Tags\TagCategory;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method TagCategory             create(array $attributes)
 * @method TagCategory|null        find(int $id, array|string $columns = ['*'])
 * @method TagCategory             findOrFail(int $id, array|string $columns = ['*'])
 * @method TagCategory             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                    save(TagCategory $model)
 * @method bool                    update(TagCategory $model, array $attributes = [], array $options = [])
 * @method bool                    delete(TagCategory $model)
 * @method Collection<TagCategory> all()
 * @method bool                    exists(array $columns)
 */
interface TagCategoryRepositoryInterface extends BaseRepositoryInterface
{
}
