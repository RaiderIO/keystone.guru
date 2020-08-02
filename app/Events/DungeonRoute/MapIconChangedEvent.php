<?php

namespace App\Events\DungeonRoute;

use App\Models\DungeonRoute;
use App\Models\MapIcon;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapIconChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var DungeonRoute $_dungeonroute */
    private $_dungeonroute;

    /** @var MapIcon $_mapIcon */
    private $_mapIcon;

    /** @var User $_user */
    private $_user;

    /**
     * Create a new event instance.
     *
     * @param $dungeonroute DungeonRoute
     * @param $mapIcon MapIcon
     * @param $user User
     * @return void
     */
    public function __construct(DungeonRoute $dungeonroute, MapIcon $mapIcon, User $user)
    {
        $this->_dungeonroute = $dungeonroute;
        $this->_mapIcon = $mapIcon;
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
        return 'mapicon-changed';
    }

    public function broadcastWith()
    {
        return [
            'mapicon' => $this->_mapIcon,
            'user'    => $this->_user->name
        ];
    }
}
