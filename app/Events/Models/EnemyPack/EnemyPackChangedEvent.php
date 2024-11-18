<?php

namespace App\Events\Models\EnemyPack;

use App\Events\Models\ModelChangedEvent;
use App\Models\EnemyPack;

/**
 * @property EnemyPack $model
 */
class EnemyPackChangedEvent extends ModelChangedEvent
{
    public function broadcastAs(): string
    {
        return 'enemypack-changed';
    }
}
