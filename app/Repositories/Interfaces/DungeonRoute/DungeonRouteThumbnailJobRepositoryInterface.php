<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteThumbnailJob                  create(array<string, mixed> $attributes)
 * @method DungeonRouteThumbnailJob|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteThumbnailJob                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteThumbnailJob                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                      save(DungeonRouteThumbnailJob $model)
 * @method bool                                      update(DungeonRouteThumbnailJob $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                      delete(DungeonRouteThumbnailJob $model)
 * @method Collection<int, DungeonRouteThumbnailJob> all()
 * @method bool                                      exists(array<int, string> $columns)
 */
interface DungeonRouteThumbnailJobRepositoryInterface extends BaseRepositoryInterface
{
}
