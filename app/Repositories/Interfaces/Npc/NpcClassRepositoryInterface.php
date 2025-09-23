<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcClass;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcClass             create(array $attributes)
 * @method NpcClass|null        find(int $id, array|string $columns = ['*'])
 * @method NpcClass             findOrFail(int $id, array|string $columns = ['*'])
 * @method NpcClass             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                 save(NpcClass $model)
 * @method bool                 update(NpcClass $model, array $attributes = [], array $options = [])
 * @method bool                 delete(NpcClass $model)
 * @method Collection<NpcClass> all()
 */
interface NpcClassRepositoryInterface extends BaseRepositoryInterface
{
}
