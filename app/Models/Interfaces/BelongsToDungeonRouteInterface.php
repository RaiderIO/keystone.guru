<?php

namespace App\Models\Interfaces;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A model owned by a single dungeon route through its `dungeon_route_id` column.
 */
interface BelongsToDungeonRouteInterface
{
    /** @return BelongsTo<DungeonRoute, covariant Model> */
    public function dungeonRoute(): BelongsTo;
}
