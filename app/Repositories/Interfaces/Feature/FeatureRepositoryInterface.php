<?php

namespace App\Repositories\Interfaces\Feature;

use App\Models\Feature\Feature;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Feature                  create(array<string, mixed> $attributes)
 * @method Feature|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Feature                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Feature                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                     save(Feature $model)
 * @method bool                     update(Feature $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                     delete(Feature $model)
 * @method Collection<int, Feature> all()
 * @method bool                     exists(array<int, string> $columns)
 */
interface FeatureRepositoryInterface extends BaseRepositoryInterface
{
}
