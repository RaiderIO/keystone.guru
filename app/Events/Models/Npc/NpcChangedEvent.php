<?php

namespace App\Events\Models\Npc;

use App\Events\Models\ModelChangedEvent;
use App\Models\Npc\Npc;

/**
 * @property Npc $model
 */
class NpcChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'npc-changed';
    }
}
