<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcDungeon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcDungeon             create(array $attributes)
 * @method NpcDungeon|null        find(int $id, array|string $columns = ['*'])
 * @method NpcDungeon             findOrFail(int $id, array|string $columns = ['*'])
 * @method NpcDungeon             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                   save(NpcDungeon $model)
 * @method bool                   update(NpcDungeon $model, array $attributes = [], array $options = [])
 * @method bool                   delete(NpcDungeon $model)
 * @method Collection<NpcDungeon> all()
 * @method bool                   exists(array $columns)
 */
interface NpcDungeonRepositoryInterface extends BaseRepositoryInterface
{
}
