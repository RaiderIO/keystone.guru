<?php

namespace App\Http\Controllers\Traits;

use App\Models\Floor\Floor;
use Illuminate\Http\Response;

trait ValidatesFloorId
{
    /**
     * @return void
     */
    public function validateFloorId(int $floorId, int $dungeonId): ?Response
    {
        if (Floor::findOrFail($floorId)->dungeon_id !== $dungeonId) {
            return response(__('controller.brushline.error.floor_not_found_in_dungeon'), 422);
        }

        return null;
    }
}
