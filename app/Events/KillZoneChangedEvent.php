<?php

namespace App\Events;

use App\Models\DungeonRoute;
use App\Models\KillZone;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KillZoneChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var DungeonRoute $_dungeonroute */
    private $_dungeonroute;

    /** @var KillZone $_killZone */
    private $_killZone;

    /** @var User $_user */
    private $_user;

    /**
     * Create a new event instance.
     *
     * @param $dungeonroute DungeonRoute
     * @param $killZone KillZone
     * @return void
     */
    public function __construct(DungeonRoute $dungeonroute, KillZone $killZone, User $user)
    {
        $this->_dungeonroute = $dungeonroute;
        $this->_killZone = $killZone;
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
        return 'killzone-changed';
    }

    public function broadcastWith()
    {
        $this->_killZone->load('killzoneenemies');
        return [
            'killzone' => $this->_killZone,
            'user' => $this->_user->name
        ];
    }
}
