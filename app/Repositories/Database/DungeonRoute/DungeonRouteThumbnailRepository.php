<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteThumbnailRepositoryInterface;
use Illuminate\Support\Carbon;

class DungeonRouteThumbnailRepository extends DatabaseRepository implements DungeonRouteThumbnailRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteThumbnail::class);
    }

    public function hasFreshThumbnailForVariant(DungeonRoute $dungeonRoute, DungeonRouteThumbnailVariant $variant): bool
    {
        // Gate on the OLDEST render among this route's thumbnails for the variant, not the newest: for a
        // multi-floor route, one freshly-rendered floor must not mask another floor whose thumbnail row
        // still exists but is stale (e.g. a failed re-render left the old row in place). Note this still
        // can't detect a floor whose thumbnail row doesn't exist at all (a wholly failed render) - that
        // requires knowing the expected floor count, which is a larger follow-up.
        $oldestRenderedAt = $dungeonRoute->dungeonRouteThumbnails()
            ->where('variant', $variant)
            ->min('updated_at');

        return $oldestRenderedAt !== null
            && Carbon::parse($oldestRenderedAt)->greaterThanOrEqualTo($dungeonRoute->updated_at);
    }
}
