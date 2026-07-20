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
        $latestRenderedAt = $dungeonRoute->dungeonRouteThumbnails()
            ->where('custom', false)
            ->where('variant', $variant)
            ->max('updated_at');

        return $latestRenderedAt !== null
            && Carbon::parse($latestRenderedAt)->greaterThanOrEqualTo($dungeonRoute->updated_at);
    }
}
