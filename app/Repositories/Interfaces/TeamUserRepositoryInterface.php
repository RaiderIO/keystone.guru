<?php

namespace App\Repositories\Interfaces;

use App\Models\TeamUser;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method TeamUser                  create(array<string, mixed> $attributes)
 * @method TeamUser|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method TeamUser                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method TeamUser                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                      save(TeamUser $model)
 * @method bool                      update(TeamUser $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                      delete(TeamUser $model)
 * @method Collection<int, TeamUser> all()
 * @method bool                      exists(array<int, string> $columns)
 */
interface TeamUserRepositoryInterface extends BaseRepositoryInterface
{
}
