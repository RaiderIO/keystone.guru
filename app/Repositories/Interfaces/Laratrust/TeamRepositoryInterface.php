<?php

namespace App\Repositories\Interfaces\Laratrust;

use App\Models\Laratrust\Team;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Team             create(array $attributes)
 * @method Team|null        find(int $id, array|string $columns = ['*'])
 * @method Team             findOrFail(int $id, array|string $columns = ['*'])
 * @method Team             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool             save(Team $model)
 * @method bool             update(Team $model, array $attributes = [], array $options = [])
 * @method bool             delete(Team $model)
 * @method Collection<Team> all()
 */
interface TeamRepositoryInterface extends BaseRepositoryInterface
{
}
