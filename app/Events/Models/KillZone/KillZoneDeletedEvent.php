<?php

namespace App\Events\Models\KillZone;

use App\Events\Models\ModelDeletedEvent;

class KillZoneDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'killzone-deleted';
    }
}
