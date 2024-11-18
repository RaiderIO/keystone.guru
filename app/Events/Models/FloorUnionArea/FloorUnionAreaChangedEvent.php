<?php

namespace App\Events\Models\FloorUnionArea;

use App\Events\Models\ModelChangedEvent;
use App\Models\Floor\FloorUnionArea;

/**
 * @property FloorUnionArea $model
 */
class FloorUnionAreaChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'floorunionarea-changed';
    }
}
