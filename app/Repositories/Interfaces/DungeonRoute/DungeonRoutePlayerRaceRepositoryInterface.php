<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoutePlayerRace                  create(array<string, mixed> $attributes)
 * @method DungeonRoutePlayerRace|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRoutePlayerRace                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRoutePlayerRace                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(DungeonRoutePlayerRace $model)
 * @method bool                                    update(DungeonRoutePlayerRace $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(DungeonRoutePlayerRace $model)
 * @method Collection<int, DungeonRoutePlayerRace> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface DungeonRoutePlayerRaceRepositoryInterface extends BaseRepositoryInterface
{
}
