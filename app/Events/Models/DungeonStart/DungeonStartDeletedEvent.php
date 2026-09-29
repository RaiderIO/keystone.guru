<?php

namespace App\Events\Models\DungeonStart;

use App\Events\Models\ModelDeletedEvent;

class DungeonStartDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'dungeonstart-deleted';
    }
}
