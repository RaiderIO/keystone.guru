<?php

namespace App\Events\Models\MountableArea;

use App\Events\Models\ModelDeletedEvent;

class MountableAreaDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'mountablearea-deleted';
    }
}
