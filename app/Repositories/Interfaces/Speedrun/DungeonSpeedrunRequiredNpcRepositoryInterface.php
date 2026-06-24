<?php

namespace App\Repositories\Interfaces\Speedrun;

use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonSpeedrunRequiredNpc                  create(array<string, mixed> $attributes)
 * @method DungeonSpeedrunRequiredNpc|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonSpeedrunRequiredNpc                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonSpeedrunRequiredNpc                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                        save(DungeonSpeedrunRequiredNpc $model)
 * @method bool                                        update(DungeonSpeedrunRequiredNpc $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                        delete(DungeonSpeedrunRequiredNpc $model)
 * @method Collection<int, DungeonSpeedrunRequiredNpc> all()
 * @method bool                                        exists(array<int, string> $columns)
 */
interface DungeonSpeedrunRequiredNpcRepositoryInterface extends BaseRepositoryInterface
{
}
