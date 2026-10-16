<?php

namespace App\Events\Models\DungeonTransport;

use App\Events\Models\ModelDeletedEvent;

class DungeonTransportDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'dungeontransport-deleted';
    }
}
