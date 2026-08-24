<?php

namespace App\Events\LiveSession;

use App\Events\ContextEvent;
use App\Models\LiveSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Override;

/**
 * Broadcast when a mapping version upgrade draft was applied onto the route this live session runs on -
 * everything the connected clients have in memory is stale, so they are told to refresh.
 *
 * This is deliberately best effort: Apply does not block on live sessions, and the session is accepted
 * as busted. Proper handling belongs in the live session rework (#3275).
 *
 * @property LiveSession $context
 */
class RouteReplacedEvent extends ContextEvent
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel[]
     */
    #[Override]
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel(sprintf('%s-live-session.%s', config('app.type'), $this->context->getRouteKey())),
        ];
    }

    #[Override]
    public function broadcastAs(): string
    {
        return 'livesession-routereplaced';
    }
}
