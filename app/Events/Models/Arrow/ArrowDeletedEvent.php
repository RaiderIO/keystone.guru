<?php

namespace App\Events\Models\Arrow;

use App\Events\Models\ModelDeletedEvent;

class ArrowDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'arrow-deleted';
    }
}
