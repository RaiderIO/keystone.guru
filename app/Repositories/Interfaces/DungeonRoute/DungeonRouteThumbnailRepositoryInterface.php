<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Thumbnail rows not backed by a usable File - either file_id is null, or it points at a File
     * row that no longer exists.
     *
     * @param  array<int, int|string>         $dungeonRouteIds Optional dungeon route IDs to restrict to; empty scans every route.
     * @return Builder<DungeonRouteThumbnail>
     */
    public function filelessThumbnailsQuery(array $dungeonRouteIds = []): Builder;

    /**
     * Thumbnail rows whose File still exists, for checking whether the underlying disk object does too.
     *
     * @param  array<int, int|string>         $dungeonRouteIds Optional dungeon route IDs to restrict to; empty scans every route.
     * @return Builder<DungeonRouteThumbnail>
     */
    public function fileBackedThumbnailsQuery(array $dungeonRouteIds = []): Builder;

    /**
     * The IDs of routes that already have at least one thumbnail of this variant, so a bulk refresh can
     * regenerate the variants a route actually has without producing new ones for routes that never had
     * them (a hero render is far more expensive than the standard one, and only the discovery hero band
     * displays it).
     *
     * @param  ?int                 $dungeonId Optional dungeon to restrict to; null scans every dungeon.
     * @return Collection<int, int>
     */
    public function getDungeonRouteIdsWithVariant(DungeonRouteThumbnailVariant $variant, ?int $dungeonId = null): Collection;
}
