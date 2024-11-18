<?php

namespace App\Events\Models\EnemyPack;

use App\Events\Models\ModelDeletedEvent;

class EnemyPackDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'enemypack-deleted';
    }
}
