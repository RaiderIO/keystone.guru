<?php

namespace App\Events;

class NpcAddedEvent extends ContextEvent
{
    public function broadcastAs()
    {
        return 'npc-added';
    }

    public function broadcastWith()
    {
        return array_merge(
            parent::broadcastWith(),
            [
                'color' => $this->_user->echo_color
            ]
        );
    }
}
