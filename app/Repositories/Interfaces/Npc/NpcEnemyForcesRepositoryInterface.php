<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcEnemyForces;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcEnemyForces                  create(array<string, mixed> $attributes)
 * @method NpcEnemyForces|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcEnemyForces                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcEnemyForces                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(NpcEnemyForces $model)
 * @method bool                            update(NpcEnemyForces $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(NpcEnemyForces $model)
 * @method Collection<int, NpcEnemyForces> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface NpcEnemyForcesRepositoryInterface extends BaseRepositoryInterface
{
}
