<?php

namespace App\Repositories\Interfaces\Feature;

use App\Models\Feature\Feature;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Feature             create(array $attributes)
 * @method Feature|null        find(int $id, array|string $columns = ['*'])
 * @method Feature             findOrFail(int $id, array|string $columns = ['*'])
 * @method Feature             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                save(Feature $model)
 * @method bool                update(Feature $model, array $attributes = [], array $options = [])
 * @method bool                delete(Feature $model)
 * @method Collection<Feature> all()
 * @method bool                exists(array $columns)
 */
interface FeatureRepositoryInterface extends BaseRepositoryInterface
{
}
