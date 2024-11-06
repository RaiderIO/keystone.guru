<?php

namespace App\Events\Models\Npc;

use App\Events\Models\ModelDeletedEvent;

class NpcDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'npc-deleted';
    }
}
