<?php

namespace App\Events\Models\FloorUnion;

use App\Events\Models\ModelChangedEvent;
use App\Models\Floor\FloorUnion;

/**
 * @property FloorUnion $model
 */
class FloorUnionChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'floorunion-changed';
    }
}
