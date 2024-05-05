<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteThumbnailJob create(array $attributes)
 * @method DungeonRouteThumbnailJob find(int $id, array $columns = [])
 * @method DungeonRouteThumbnailJob findOrFail(int $id, array $columns = [])
 * @method DungeonRouteThumbnailJob findOrNew(int $id, array $columns = [])
 * @method bool save(DungeonRouteThumbnailJob $model)
 * @method bool update(DungeonRouteThumbnailJob $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRouteThumbnailJob $model)
 * @method Collection<DungeonRouteThumbnailJob> all()
 */
interface DungeonRouteThumbnailJobRepositoryInterface extends BaseRepositoryInterface
{

}
