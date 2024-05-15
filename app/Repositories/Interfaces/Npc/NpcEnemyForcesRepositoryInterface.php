<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcEnemyForces;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcEnemyForces create(array $attributes)
 * @method NpcEnemyForces find(int $id, array $columns = [])
 * @method NpcEnemyForces findOrFail(int $id, array $columns = [])
 * @method NpcEnemyForces findOrNew(int $id, array $columns = [])
 * @method bool save(NpcEnemyForces $model)
 * @method bool update(NpcEnemyForces $model, array $attributes = [], array $options = [])
 * @method bool delete(NpcEnemyForces $model)
 * @method Collection<NpcEnemyForces> all()
 */
interface NpcEnemyForcesRepositoryInterface extends BaseRepositoryInterface
{

}
