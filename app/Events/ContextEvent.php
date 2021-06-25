<?php

namespace App\Events;

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
        $this->_user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel(sprintf('%s-route-edit.%s', config('app.type'), $this->_context->getRouteKey())),
            new PresenceChannel(sprintf('%s-live-session.%s', config('app.type'), $this->_context->getRouteKey())),
            new PresenceChannel(sprintf('%s-dungeon-edit.%s', config('app.type'), $this->_context->getRouteKey()))
        ];
    }

    public function broadcastWith(): array
    {
        return [
            '__name'            => $this->broadcastAs(),
            'context_route_key' => $this->_context->getRouteKey(),
            'context_class'     => get_class($this->_context),
            'user'              => $this->_user
        ];
    }

    public abstract function broadcastAs(): string;
}
