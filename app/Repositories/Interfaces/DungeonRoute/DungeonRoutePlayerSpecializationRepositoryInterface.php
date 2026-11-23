<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoutePlayerSpecialization                  create(array<string, mixed> $attributes)
 * @method DungeonRoutePlayerSpecialization|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRoutePlayerSpecialization                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRoutePlayerSpecialization                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                              save(DungeonRoutePlayerSpecialization $model)
 * @method bool                                              update(DungeonRoutePlayerSpecialization $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                              delete(DungeonRoutePlayerSpecialization $model)
 * @method Collection<int, DungeonRoutePlayerSpecialization> all()
 * @method bool                                              exists(array<int, string> $columns)
 */
interface DungeonRoutePlayerSpecializationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Replaces the route's rows with one row per id.
     *
     * @param array<int, int|string> $ids
     */
    public function replaceForDungeonRoute(DungeonRoute $dungeonRoute, array $ids): void;
}
