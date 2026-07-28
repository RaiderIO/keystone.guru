<?php

namespace App\Events\Models\EnemyForcesCheckpoint;

use App\Events\Models\ModelChangedEvent;
use App\Models\EnemyForcesCheckpoint;

/**
 * @property EnemyForcesCheckpoint $model
 */
class EnemyForcesCheckpointChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'enemyforcescheckpoint-changed';
    }
}
