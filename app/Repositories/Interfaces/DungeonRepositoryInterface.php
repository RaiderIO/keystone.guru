<?php

namespace App\Repositories\Interfaces;

use App\Models\Dungeon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Dungeon create(array $attributes)
 * @method Dungeon find(int $id, array $columns = [])
 * @method Dungeon findOrFail(int $id, array $columns = [])
 * @method Dungeon findOrNew(int $id, array $columns = [])
 * @method bool save(Dungeon $model)
 * @method bool update(Dungeon $model, array $attributes = [], array $options = [])
 * @method bool delete(Dungeon $model)
 * @method Collection<Dungeon> all()
 */
interface DungeonRepositoryInterface extends BaseRepositoryInterface
{

}
