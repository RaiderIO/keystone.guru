<?php

namespace App\Events\LiveSession;

use App\Events\ContextEvent;
use App\Models\LiveSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;

/**
 * Class StopEvent
 *
 * @author Wouter
 *
 * @since 15/06/2021
 *
 * @property LiveSession $context
 */
class StopEvent extends ContextEvent
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel[]
     */
    #[\Override]
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel(sprintf('%s-live-session.%s', config('app.type'), $this->context->getRouteKey())),
        ];
    }

    #[\Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'expires_in' => $this->context->getExpiresInSeconds(),
        ]);
    }

    public function broadcastAs(): string
    {
        return 'livesession-stop';
    }
}
