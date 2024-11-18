<?php

namespace App\Events\Models\EnemyPatrol;

use App\Events\Models\ModelDeletedEvent;

class EnemyPatrolDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'enemypatrol-deleted';
    }
}
