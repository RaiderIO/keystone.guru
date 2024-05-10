<?php

namespace App\Repositories\Interfaces\Tags;

use App\Models\Tags\Tag;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Tag create(array $attributes)
 * @method Tag find(int $id, array $columns = [])
 * @method Tag findOrFail(int $id, array $columns = [])
 * @method Tag findOrNew(int $id, array $columns = [])
 * @method bool save(Tag $model)
 * @method bool update(Tag $model, array $attributes = [], array $options = [])
 * @method bool delete(Tag $model)
 * @method Collection<Tag> all()
 */
interface TagRepositoryInterface extends BaseRepositoryInterface
{

}
