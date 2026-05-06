<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcType;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcType             create(array $attributes)
 * @method NpcType|null        find(int $id, array|string $columns = ['*'])
 * @method NpcType             findOrFail(int $id, array|string $columns = ['*'])
 * @method NpcType             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                save(NpcType $model)
 * @method bool                update(NpcType $model, array $attributes = [], array $options = [])
 * @method bool                delete(NpcType $model)
 * @method Collection<NpcType> all()
 */
interface NpcTypeRepositoryInterface extends BaseRepositoryInterface
{
}
