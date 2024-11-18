<?php

namespace App\Events\Models\PridefulEnemy;

use App\Events\Models\ModelDeletedEvent;

class PridefulEnemyDeletedEvent extends ModelDeletedEvent
{
    public function broadcastAs(): string
    {
        return 'pridefulenemy-deleted';
    }
}
