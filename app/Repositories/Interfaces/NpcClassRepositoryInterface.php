<?php

namespace App\Repositories\Interfaces;

use App\Models\NpcClass;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcClass create(array $attributes)
 * @method NpcClass find(int $id, array $columns = [])
 * @method NpcClass findOrFail(int $id, array $columns = [])
 * @method NpcClass findOrNew(int $id, array $columns = [])
 * @method bool save(NpcClass $model)
 * @method bool update(NpcClass $model, array $attributes = [], array $options = [])
 * @method bool delete(NpcClass $model)
 * @method Collection<NpcClass> all()
 */
interface NpcClassRepositoryInterface extends BaseRepositoryInterface
{

}
