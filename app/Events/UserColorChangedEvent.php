<?php

namespace App\Events;

class UserColorChangedEvent extends ContextEvent
{
    public function broadcastWith(): array
    {
        return array_merge(
            parent::broadcastWith(),
            [
                'color' => $this->_user->echo_color
            ]
        );
    }

    public function broadcastAs(): string
    {
        return 'user-color-changed';
    }
}
