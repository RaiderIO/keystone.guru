<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteAffixGroup create(array $attributes)
 * @method DungeonRouteAffixGroup|null find(int $id, array|string $columns = ['*'])
 * @method DungeonRouteAffixGroup findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonRouteAffixGroup findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(DungeonRouteAffixGroup $model)
 * @method bool update(DungeonRouteAffixGroup $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRouteAffixGroup $model)
 * @method Collection<DungeonRouteAffixGroup> all()
 */
interface DungeonRouteAffixGroupRepositoryInterface extends BaseRepositoryInterface
{

}
