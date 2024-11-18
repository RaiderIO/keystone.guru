<?php

namespace App\Events\Models\EnemyPatrol;

use App\Events\Models\ModelChangedEvent;
use App\Models\EnemyPatrol;

/**
 * @property EnemyPatrol $model
 */
class EnemyPatrolChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'enemypatrol-changed';
    }
}
