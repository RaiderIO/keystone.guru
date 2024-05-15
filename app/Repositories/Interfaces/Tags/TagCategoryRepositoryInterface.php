<?php

namespace App\Repositories\Interfaces\Tags;

use App\Models\Tags\TagCategory;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method TagCategory create(array $attributes)
 * @method TagCategory find(int $id, array $columns = [])
 * @method TagCategory findOrFail(int $id, array $columns = [])
 * @method TagCategory findOrNew(int $id, array $columns = [])
 * @method bool save(TagCategory $model)
 * @method bool update(TagCategory $model, array $attributes = [], array $options = [])
 * @method bool delete(TagCategory $model)
 * @method Collection<TagCategory> all()
 */
interface TagCategoryRepositoryInterface extends BaseRepositoryInterface
{

}
