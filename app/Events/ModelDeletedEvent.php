<?php

namespace App\Events;

class ModelDeletedEvent extends ContextModelEvent
{
    public function broadcastAs()
    {
        return 'model-changed';
    }
}
