<?php

namespace App\Repositories\Interfaces;

use App\Models\PublishedState;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PublishedState create(array $attributes)
 * @method PublishedState find(int $id, array $columns = [])
 * @method PublishedState findOrFail(int $id, array $columns = [])
 * @method PublishedState findOrNew(int $id, array $columns = [])
 * @method bool save(PublishedState $model)
 * @method bool update(PublishedState $model, array $attributes = [], array $options = [])
 * @method bool delete(PublishedState $model)
 * @method Collection<PublishedState> all()
 */
interface PublishedStateRepositoryInterface extends BaseRepositoryInterface
{

}
