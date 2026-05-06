<?php

namespace App\Repositories\Interfaces;

use App\Models\PublishedState;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PublishedState             create(array $attributes)
 * @method PublishedState|null        find(int $id, array|string $columns = ['*'])
 * @method PublishedState             findOrFail(int $id, array|string $columns = ['*'])
 * @method PublishedState             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                       save(PublishedState $model)
 * @method bool                       update(PublishedState $model, array $attributes = [], array $options = [])
 * @method bool                       delete(PublishedState $model)
 * @method Collection<PublishedState> all()
 */
interface PublishedStateRepositoryInterface extends BaseRepositoryInterface
{
}
