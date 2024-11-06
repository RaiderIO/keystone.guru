<?php

namespace App\Events\Models\DungeonFloorSwitchMarker;

use App\Events\Models\ModelDeletedEvent;

class DungeonFloorSwitchMarkerDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'dungeonfloorswitchmarker-deleted';
    }
}
