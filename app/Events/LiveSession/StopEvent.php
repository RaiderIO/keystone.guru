<?php

namespace App\Events\LiveSession;

use App\Events\ContextEvent;
use App\Models\LiveSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;

/**
 * Class StopEvent
 * @package App\Events\LiveSession
 * @author Wouter
 * @since 15/06/2021
 *
 * @property LiveSession $_context
 */
class StopEvent extends ContextEvent
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel(sprintf('%s-live-session.%s', config('app.type'), $this->_context->getRouteKey())),
        ];
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'expires_in' => $this->_context->getExpiresInSeconds()
        ]);
    }

    public function broadcastAs(): string
    {
        return 'livesession-stop';
    }
}
