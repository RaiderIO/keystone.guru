<?php

namespace App\Events;

use App\Models\Dungeon;
use App\Models\DungeonRoute;
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
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var Model $_context */
    protected Model $_context;

    /** @var User $_user */
    protected User $_user;

    /**
     * Create a new event instance.
     *
     * @param $context Model
     * @param $user User
     * @return void
     */
    public function __construct(Model $context, User $user)
    {
        $this->_context = $context;
        $this->_user    = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): array
    {
        $result = [];

        if ($this->_context instanceof DungeonRoute) {
            $result[] = new PresenceChannel(sprintf('%s-route-edit.%s', config('app.type'), $this->_context->getRouteKey()));
        } else if ($this->_context instanceof LiveSession) {
            $result[] = new PresenceChannel(sprintf('%s-live-session.%s', config('app.type'), $this->_context->getRouteKey()));
        } else if ($this->_context instanceof Dungeon) {
            $result[] = new PresenceChannel(sprintf('%s-dungeon-edit.%s', config('app.type'), $this->_context->getRouteKey()));
        }

        return $result;
    }

    public function broadcastWith(): array
    {
        return [
            '__name'            => $this->broadcastAs(),
            'context_route_key' => $this->_context->getRouteKey(),
            'context_class'     => get_class($this->_context),
            'user'              => [
                'color'      => $this->_user->echo_color,
                'name'       => $this->_user->name,
                'public_key' => $this->_user->public_key,
            ],
        ];
    }

    public abstract function broadcastAs(): string;
}
