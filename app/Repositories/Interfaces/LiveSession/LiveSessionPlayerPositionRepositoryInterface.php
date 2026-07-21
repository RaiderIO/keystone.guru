<?php

namespace App\Repositories\Interfaces\LiveSession;

use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionPlayerPosition                  create(array<string, mixed> $attributes)
 * @method LiveSessionPlayerPosition|null              find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionPlayerPosition                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionPlayerPosition                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                       save(LiveSessionPlayerPosition $model)
 * @method bool                                       update(LiveSessionPlayerPosition $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                       delete(LiveSessionPlayerPosition $model)
 * @method Collection<int, LiveSessionPlayerPosition> all()
 * @method bool                                       exists(array<int, string> $columns)
 */
interface LiveSessionPlayerPositionRepositoryInterface extends BaseRepositoryInterface
{
}
