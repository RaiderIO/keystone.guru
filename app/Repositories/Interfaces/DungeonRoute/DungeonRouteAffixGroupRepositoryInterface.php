<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteAffixGroup                  create(array<string, mixed> $attributes)
 * @method DungeonRouteAffixGroup|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteAffixGroup                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteAffixGroup                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(DungeonRouteAffixGroup $model)
 * @method bool                                    update(DungeonRouteAffixGroup $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(DungeonRouteAffixGroup $model)
 * @method Collection<int, DungeonRouteAffixGroup> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface DungeonRouteAffixGroupRepositoryInterface extends BaseRepositoryInterface
{
}
