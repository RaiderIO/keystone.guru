<?php

namespace App\Repositories\Interfaces\Speedrun;

use App\Models\Speedrun\DungeonSpeedrunDifficulty;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonSpeedrunDifficulty                  create(array<string, mixed> $attributes)
 * @method DungeonSpeedrunDifficulty|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonSpeedrunDifficulty                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonSpeedrunDifficulty                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                       save(DungeonSpeedrunDifficulty $model)
 * @method bool                                       update(DungeonSpeedrunDifficulty $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                       delete(DungeonSpeedrunDifficulty $model)
 * @method Collection<int, DungeonSpeedrunDifficulty> all()
 * @method bool                                       exists(array<int, string> $columns)
 */
interface DungeonSpeedrunDifficultyRepositoryInterface extends BaseRepositoryInterface
{
}
