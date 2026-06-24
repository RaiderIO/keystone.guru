<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRouteThumbnail                  create(array<string, mixed> $attributes)
 * @method DungeonRouteThumbnail|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteThumbnail                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonRouteThumbnail                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                   save(DungeonRouteThumbnail $model)
 * @method bool                                   update(DungeonRouteThumbnail $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                   delete(DungeonRouteThumbnail $model)
 * @method Collection<int, DungeonRouteThumbnail> all()
 * @method bool                                   exists(array<int, string> $columns)
 */
interface DungeonRouteThumbnailRepositoryInterface extends BaseRepositoryInterface
{
}
