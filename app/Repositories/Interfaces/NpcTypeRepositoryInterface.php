<?php

namespace App\Repositories\Interfaces;

use App\Models\NpcType;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcType create(array $attributes)
 * @method NpcType find(int $id, array $columns = [])
 * @method NpcType findOrFail(int $id, array $columns = [])
 * @method NpcType findOrNew(int $id, array $columns = [])
 * @method bool save(NpcType $model)
 * @method bool update(NpcType $model, array $attributes = [], array $options = [])
 * @method bool delete(NpcType $model)
 * @method Collection<NpcType> all()
 */
interface NpcTypeRepositoryInterface extends BaseRepositoryInterface
{

}
