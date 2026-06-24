<?php

namespace App\Repositories\Interfaces\Speedrun;

use App\Models\Speedrun\DungeonSpeedrunRequiredNpcNpc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonSpeedrunRequiredNpcNpc                  create(array<string, mixed> $attributes)
 * @method DungeonSpeedrunRequiredNpcNpc|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonSpeedrunRequiredNpcNpc                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonSpeedrunRequiredNpcNpc                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                           save(DungeonSpeedrunRequiredNpcNpc $model)
 * @method bool                                           update(DungeonSpeedrunRequiredNpcNpc $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                           delete(DungeonSpeedrunRequiredNpcNpc $model)
 * @method Collection<int, DungeonSpeedrunRequiredNpcNpc> all()
 * @method bool                                           exists(array<string, mixed> $columns)
 */
interface DungeonSpeedrunRequiredNpcNpcRepositoryInterface extends BaseRepositoryInterface
{
}
