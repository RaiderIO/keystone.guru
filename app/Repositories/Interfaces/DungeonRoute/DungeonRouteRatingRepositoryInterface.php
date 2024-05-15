<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteRating;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteRating create(array $attributes)
 * @method DungeonRouteRating|null find(int $id, array|string $columns = ['*'])
 * @method DungeonRouteRating findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonRouteRating findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(DungeonRouteRating $model)
 * @method bool update(DungeonRouteRating $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRouteRating $model)
 * @method Collection<DungeonRouteRating> all()
 */
interface DungeonRouteRatingRepositoryInterface extends BaseRepositoryInterface
{

}
