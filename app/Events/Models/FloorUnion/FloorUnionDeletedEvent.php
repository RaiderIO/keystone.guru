<?php

namespace App\Events\Models\FloorUnion;

use App\Events\Models\ModelDeletedEvent;

class FloorUnionDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'floorunion-deleted';
    }
}
