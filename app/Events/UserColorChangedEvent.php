<?php

namespace App\Events;

use Override;

class UserColorChangedEvent extends ContextEvent
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'color' => $this->user->echo_color,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'user-color-changed';
    }
}
