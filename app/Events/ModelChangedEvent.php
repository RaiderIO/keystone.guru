<?php

namespace App\Events;

class ModelChangedEvent extends ContextModelEvent
{
    public function broadcastAs()
    {
        return 'model-changed';
    }
}
