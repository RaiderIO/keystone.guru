<?php

namespace App\Events\Models\MapIcon;

use App\Events\Models\ModelDeletedEvent;

class MapIconDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'mapicon-deleted';
    }
}
