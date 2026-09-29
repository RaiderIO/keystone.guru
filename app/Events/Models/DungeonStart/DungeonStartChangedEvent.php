<?php

namespace App\Events\Models\DungeonStart;

use App\Events\Models\ModelChangedEvent;
use App\Models\DungeonStart;

/**
 * @property DungeonStart $model
 */
class DungeonStartChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'dungeonstart-changed';
    }
}
