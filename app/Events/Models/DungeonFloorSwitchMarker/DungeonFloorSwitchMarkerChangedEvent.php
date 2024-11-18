<?php

namespace App\Events\Models\DungeonFloorSwitchMarker;

use App\Events\Models\ModelChangedEvent;
use App\Models\DungeonFloorSwitchMarker;

/**
 * @property DungeonFloorSwitchMarker $model
 */
class DungeonFloorSwitchMarkerChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'dungeonfloorswitchmarker-changed';
    }
}
