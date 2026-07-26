<?php

namespace App\Events\Models\EnemyForcesRegion;

use App\Events\Models\ModelChangedEvent;
use App\Models\EnemyForcesRegion;

/**
 * @property EnemyForcesRegion $model
 */
class EnemyForcesRegionChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'enemyforcesregion-changed';
    }
}
