<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Interfaces\BelongsToDungeonRouteInterface;
use Teapot\StatusCode\Http;

trait ScopesToDungeonRoute
{
    /**
     * Child objects are bound by their own id, independent of the route key in the URL - a child of
     * any other route, or an orphaned one, is not found through this one.
     */
    protected function abortUnlessBelongsToDungeonRoute(
        DungeonRoute                    $dungeonRoute,
        ?BelongsToDungeonRouteInterface $model,
    ): void {
        abort_if(
            $model !== null && (int)$model->dungeonRoute()->getParentKey() !== $dungeonRoute->id,
            Http::NOT_FOUND,
        );
    }
}
