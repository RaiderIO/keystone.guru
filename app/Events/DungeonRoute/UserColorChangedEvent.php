<?php

namespace App\Events\DungeonRoute;

use App\Models\DungeonRoute;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserColorChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var DungeonRoute $_dungeonroute */
    private $_dungeonroute;

    /** @var User $_user */
    private $_user;

    /**
     * Create a new event instance.
     *
     * @param $dungeonroute DungeonRoute
     * @param User $user
     * @return void
     */
    public function __construct(DungeonRoute $dungeonroute, User $user)
    {
        $this->_dungeonroute = $dungeonroute;
        $this->_user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel(sprintf('%s-route-edit.%s', env('APP_TYPE'), $this->_dungeonroute->public_key));
    }

    public function broadcastAs()
    {
        return 'user-color-changed';
    }

    public function broadcastWith()
    {
        return [
            'name'  => $this->_user->name,
            'color' => $this->_user->echo_color
        ];
    }
}
