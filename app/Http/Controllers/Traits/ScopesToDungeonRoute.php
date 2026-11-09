<?php

namespace App\Http\Controllers\Traits;

use App\Models\Arrow;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession;
use App\Models\Path;
use Teapot\StatusCode\Http;

trait ScopesToDungeonRoute
{
    /**
     * Child objects are bound by their own id, independent of the route key in the URL - a child of
     * any other route, or an orphaned one, is not found through this one.
     */
    protected function abortUnlessBelongsToDungeonRoute(
        DungeonRoute                                   $dungeonRoute,
        Arrow|Brushline|Path|KillZone|LiveSession|null $model,
    ): void {
        abort_if(
            $model !== null && (int)$model->dungeon_route_id !== $dungeonRoute->id,
            Http::NOT_FOUND,
        );
    }
}
