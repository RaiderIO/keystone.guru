<?php

namespace App\Repositories\Interfaces\Speedrun;

use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonSpeedrunRequiredNpc create(array $attributes)
 * @method DungeonSpeedrunRequiredNpc|null find(int $id, array|string $columns = ['*'])
 * @method DungeonSpeedrunRequiredNpc findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonSpeedrunRequiredNpc findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(DungeonSpeedrunRequiredNpc $model)
 * @method bool update(DungeonSpeedrunRequiredNpc $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonSpeedrunRequiredNpc $model)
 * @method Collection<DungeonSpeedrunRequiredNpc> all()
 */
interface DungeonSpeedrunRequiredNpcRepositoryInterface extends BaseRepositoryInterface
{

}
