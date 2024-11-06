<?php

namespace App\Events\Models\FloorUnionArea;

use App\Events\Models\ModelDeletedEvent;

class FloorUnionAreaDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'floorunionarea-deleted';
    }
}
