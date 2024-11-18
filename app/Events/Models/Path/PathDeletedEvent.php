<?php

namespace App\Events\Models\Path;

use App\Events\Models\ModelDeletedEvent;

class PathDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'path-deleted';
    }
}
