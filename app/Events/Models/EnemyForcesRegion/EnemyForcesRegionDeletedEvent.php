<?php

namespace App\Events\Models\EnemyForcesRegion;

use App\Events\Models\ModelDeletedEvent;

class EnemyForcesRegionDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'enemyforcesregion-deleted';
    }
}
