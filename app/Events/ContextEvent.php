<?php

namespace App\Events;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession;
use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class ContextEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(protected Model $context, protected User $user)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): array
    {
        $result = [];

        if ($this->context instanceof DungeonRoute) {
            $result[] = new PresenceChannel(sprintf('%s-route-edit.%s', config('app.type'), $this->context->getRouteKey()));
        } else if ($this->context instanceof LiveSession) {
            $result[] = new PresenceChannel(sprintf('%s-live-session.%s', config('app.type'), $this->context->getRouteKey()));
        } else if ($this->context instanceof Dungeon) {
            $result[] = new PresenceChannel(sprintf('%s-mapping-version-edit.%s', config('app.type'), $this->context->getRouteKey()));
        }

        return $result;
    }

    public function broadcastWith(): array
    {
        return [
            '__name'            => $this->broadcastAs(),
            'context_route_key' => $this->context->getRouteKey(),
            'context_class'     => $this->context::class,
            'user'              => [
                'color'      => $this->user->echo_color,
                'name'       => $this->user->name,
                'public_key' => $this->user->public_key,
            ],
        ];
    }

    abstract public function broadcastAs(): string;
}
