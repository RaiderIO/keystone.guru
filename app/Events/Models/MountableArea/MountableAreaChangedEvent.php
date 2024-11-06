<?php

namespace App\Events\Models\MountableArea;

use App\Events\Models\ModelChangedEvent;
use App\Models\MountableArea;

/**
 * @property MountableArea $model
 */
class MountableAreaChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'mountablearea-changed';
    }
}
