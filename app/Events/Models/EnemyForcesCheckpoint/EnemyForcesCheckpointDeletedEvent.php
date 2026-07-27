<?php

namespace App\Events\Models\EnemyForcesCheckpoint;

use App\Events\Models\ModelDeletedEvent;

class EnemyForcesCheckpointDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'enemyforcescheckpoint-deleted';
    }
}
