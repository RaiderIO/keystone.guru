<?php

namespace App\Events\Models\DungeonTransport;

use App\Events\Models\ModelChangedEvent;

class DungeonTransportChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'dungeontransport-changed';
    }
}
