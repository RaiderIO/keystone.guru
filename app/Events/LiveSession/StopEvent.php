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
    public function broadcastOn()
    {
        return [
            new PresenceChannel(sprintf('%s-live-session.%s', config('app.type'), $this->_context->dungeonroute->getRouteKey())),
        ];
    }

    public function broadcastAs()
    {
        return 'livesession-stop';
    }

    public function broadcastWith()
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'expires_at' => $this->_context->expires_at
        ]);
    }

}
