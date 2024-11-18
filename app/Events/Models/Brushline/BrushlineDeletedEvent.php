<?php

namespace App\Events\Models\Brushline;

use App\Events\Models\ModelDeletedEvent;

class BrushlineDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'brushline-deleted';
    }
}
