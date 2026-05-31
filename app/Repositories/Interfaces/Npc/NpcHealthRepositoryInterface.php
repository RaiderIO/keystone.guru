<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcHealth;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcHealth             create(array $attributes)
 * @method NpcHealth|null        find(int $id, array|string $columns = ['*'])
 * @method NpcHealth             findOrFail(int $id, array|string $columns = ['*'])
 * @method NpcHealth             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                  save(NpcHealth $model)
 * @method bool                  update(NpcHealth $model, array $attributes = [], array $options = [])
 * @method bool                  delete(NpcHealth $model)
 * @method Collection<NpcHealth> all()
 * @method bool                  exists(array $columns)
 */
interface NpcHealthRepositoryInterface extends BaseRepositoryInterface
{
}
