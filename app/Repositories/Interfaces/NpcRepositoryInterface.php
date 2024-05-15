<?php

namespace App\Repositories\Interfaces;

use App\Models\Npc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Npc create(array $attributes)
 * @method Npc find(int $id, array $columns = [])
 * @method Npc findOrFail(int $id, array $columns = [])
 * @method Npc findOrNew(int $id, array $columns = [])
 * @method bool save(Npc $model)
 * @method bool update(Npc $model, array $attributes = [], array $options = [])
 * @method bool delete(Npc $model)
 * @method Collection<Npc> all()
 */
interface NpcRepositoryInterface extends BaseRepositoryInterface
{

}
