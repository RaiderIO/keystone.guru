<?php

namespace App\Events;

class UserColorChangedEvent extends ContextEvent
{
    public function broadcastAs()
    {
        return 'user-color-changed';
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
