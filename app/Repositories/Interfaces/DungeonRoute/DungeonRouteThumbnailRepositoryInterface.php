<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
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
    /**
     * A thumbnail variant is fresh when one exists and was rendered at or after the route's last content
     * change. Thumbnail renders intentionally do not bump the route's updated_at, so an edited route reliably
     * reads as stale until the variant is regenerated.
     */
    public function hasFreshThumbnailForVariant(DungeonRoute $dungeonRoute, DungeonRouteThumbnailVariant $variant): bool;
}
