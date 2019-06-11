<?php

namespace App\Events;

use App\Models\Brushline;
use App\Models\DungeonRoute;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrushlineChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var DungeonRoute $_dungeonroute */
    private $_dungeonroute;

    /** @var Brushline $_brushline */
    private $_brushline;

    /** @var User $_user */
    private $_user;

    /**
     * Create a new event instance.
     *
     * @param $dungeonroute DungeonRoute
     * @param $brushline Brushline
     * @param $user User
     * @return void
     */
    public function __construct(DungeonRoute $dungeonroute, Brushline $brushline, User $user)
    {
        $this->_dungeonroute = $dungeonroute;
        $this->_brushline = $brushline;
        $this->_user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel(sprintf('route-edit.%s', $this->_dungeonroute->public_key));
    }

    public function broadcastAs()
    {
        return 'brushline-changed';
    }

    public function broadcastWith()
    {
        return [
            'brushline' => $this->_brushline,
            'user' => $this->_user->name
        ];
    }
}
