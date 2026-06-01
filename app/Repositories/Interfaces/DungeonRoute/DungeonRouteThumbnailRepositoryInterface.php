<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteThumbnail             create(array $attributes)
 * @method DungeonRouteThumbnail|null        find(int $id, array|string $columns = ['*'])
 * @method DungeonRouteThumbnail             findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonRouteThumbnail             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                              save(DungeonRouteThumbnail $model)
 * @method bool                              update(DungeonRouteThumbnail $model, array $attributes = [], array $options = [])
 * @method bool                              delete(DungeonRouteThumbnail $model)
 * @method Collection<DungeonRouteThumbnail> all()
 * @method bool                              exists(array $columns)
 */
interface DungeonRouteThumbnailRepositoryInterface extends BaseRepositoryInterface
{
}
