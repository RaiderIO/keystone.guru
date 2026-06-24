<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteRating;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteRating                  create(array<string, mixed> $attributes)
 * @method DungeonRouteRating|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteRating                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteRating                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                save(DungeonRouteRating $model)
 * @method bool                                update(DungeonRouteRating $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                delete(DungeonRouteRating $model)
 * @method Collection<int, DungeonRouteRating> all()
 * @method bool                                exists(array<int, string> $columns)
 */
interface DungeonRouteRatingRepositoryInterface extends BaseRepositoryInterface
{
}
