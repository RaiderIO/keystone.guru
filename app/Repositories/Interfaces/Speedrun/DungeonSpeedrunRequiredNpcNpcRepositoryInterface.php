<?php

namespace App\Repositories\Interfaces\Speedrun;

use App\Models\Speedrun\DungeonSpeedrunRequiredNpcNpc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonSpeedrunRequiredNpcNpc             create(array $attributes)
 * @method DungeonSpeedrunRequiredNpcNpc|null        find(int $id, array|string $columns = ['*'])
 * @method DungeonSpeedrunRequiredNpcNpc             findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonSpeedrunRequiredNpcNpc             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                                      save(DungeonSpeedrunRequiredNpcNpc $model)
 * @method bool                                      update(DungeonSpeedrunRequiredNpcNpc $model, array $attributes = [], array $options = [])
 * @method bool                                      delete(DungeonSpeedrunRequiredNpcNpc $model)
 * @method Collection<DungeonSpeedrunRequiredNpcNpc> all()
 * @method bool                                      exists(array $columns)
 */
interface DungeonSpeedrunRequiredNpcNpcRepositoryInterface extends BaseRepositoryInterface
{
}
