<?php

namespace App\Http\Controllers\Traits;

use App\Models\Arrow;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;
use Mockery\Exception;
use Teapot\StatusCode\Http;

trait ListsArrows
{
    /**
     * Lists all arrows on a specific floor of a dungeon route.
     */
    public function listArrows(int $floorId, ?DungeonRoute $dungeonRoute = null): Collection
    {
        try {
            $result = Arrow::with('polyline')
                ->where('dungeon_route_id', $dungeonRoute->id)
                ->where('floor_id', $floorId)
                ->get();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
