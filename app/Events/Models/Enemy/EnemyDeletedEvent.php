<?php

namespace App\Events\Models\Enemy;

use App\Events\Models\ModelDeletedEvent;

class EnemyDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'enemy-deleted';
    }
}
