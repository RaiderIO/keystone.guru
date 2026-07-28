<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteThumbnailRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DungeonRouteThumbnailRepository extends DatabaseRepository implements DungeonRouteThumbnailRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteThumbnail::class);
    }

    public function hasFreshThumbnailForVariant(DungeonRoute $dungeonRoute, DungeonRouteThumbnailVariant $variant): bool
    {
        $mappingVersion = $dungeonRoute->mappingVersion;
        if ($mappingVersion === null) { // @phpstan-ignore identical.alwaysFalse
            return false;
        }

        $expectedFloorCount = $dungeonRoute->dungeon
            ->floorsForMapFacade($mappingVersion, true)
            ->active()
            ->count();

        $existingThumbnailCount = $dungeonRoute->dungeonRouteThumbnails()
            ->where('variant', $variant)
            ->count();

        // A floor with zero thumbnail rows for the variant (a render that never ran or never succeeded)
        // has no updated_at to compare, so it must be caught here before the min() staleness check below -
        // otherwise it stays invisible to the freshness check until the route is edited again.
        if ($existingThumbnailCount < $expectedFloorCount) {
            return false;
        }

        // Gate on the OLDEST render among this route's thumbnails for the variant, not the newest: for a
        // multi-floor route, one freshly-rendered floor must not mask another floor whose thumbnail row
        // still exists but is stale (e.g. a failed re-render left the old row in place).
        $oldestRenderedAt = $dungeonRoute->dungeonRouteThumbnails()
            ->where('variant', $variant)
            ->min('updated_at');

        return $oldestRenderedAt !== null
            && Carbon::parse($oldestRenderedAt)->greaterThanOrEqualTo($dungeonRoute->updated_at);
    }

    public function filelessThumbnailsQuery(array $dungeonRouteIds = []): Builder
    {
        // whereDoesntHave('file') covers both a null file_id and a file_id pointing at a missing row.
        return $this->scopeToDungeonRoutes(
            DungeonRouteThumbnail::query()->whereDoesntHave('file'),
            $dungeonRouteIds,
        );
    }

    public function fileBackedThumbnailsQuery(array $dungeonRouteIds = []): Builder
    {
        // The model's default $with would also hydrate the unused floor relation for every row.
        return $this->scopeToDungeonRoutes(
            DungeonRouteThumbnail::query()
                ->whereHas('file')
                ->without('floor'),
            $dungeonRouteIds,
        );
    }

    /**
     * @param  Builder<DungeonRouteThumbnail> $query
     * @param  array<int, int|string>         $dungeonRouteIds
     * @return Builder<DungeonRouteThumbnail>
     */
    private function scopeToDungeonRoutes(Builder $query, array $dungeonRouteIds): Builder
    {
        if ($dungeonRouteIds === []) {
            return $query;
        }

        return $query->whereIn('dungeon_route_id', $dungeonRouteIds);
    }
}
