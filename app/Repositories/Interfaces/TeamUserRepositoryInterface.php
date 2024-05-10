<?php

namespace App\Repositories\Interfaces;

use App\Models\TeamUser;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method TeamUser create(array $attributes)
 * @method TeamUser find(int $id, array $columns = [])
 * @method TeamUser findOrFail(int $id, array $columns = [])
 * @method TeamUser findOrNew(int $id, array $columns = [])
 * @method bool save(TeamUser $model)
 * @method bool update(TeamUser $model, array $attributes = [], array $options = [])
 * @method bool delete(TeamUser $model)
 * @method Collection<TeamUser> all()
 */
interface TeamUserRepositoryInterface extends BaseRepositoryInterface
{

}
