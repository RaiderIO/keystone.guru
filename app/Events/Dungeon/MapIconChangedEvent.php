<?php

namespace App\Events\Dungeon;

use App\Models\Dungeon;
use App\Models\MapIcon;
use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapIconChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var Dungeon $_dungeon */
    private Dungeon $_dungeon;

    /** @var MapIcon $_mapIcon */
    private MapIcon $_mapIcon;

    /** @var User $_user */
    private User $_user;

    /**
     * Create a new event instance.
     *
     * @param $dungeon Dungeon
     * @param $mapIcon MapIcon
     * @param $user User
     * @return void
     */
    public function __construct(Dungeon $dungeon, MapIcon $mapIcon, User $user)
    {
        $this->_dungeon = $dungeon;
        $this->_mapIcon = $mapIcon;
        $this->_user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel(sprintf('%s-dungeon-edit.%s', env('APP_TYPE'), $this->_dungeon->id));
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
