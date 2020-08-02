<?php

namespace App\Events\Dungeon;

use App\Models\Dungeon;
use App\Models\MapIcon;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapIconDeletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var Dungeon $_dungeon */
    private Dungeon $_dungeon;

    /** @var int $_id */
    private int $_id;

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
        $this->_id = $mapIcon->id;
        $this->_user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel(sprintf('%s-dungeon-edit.%s', env('APP_TYPE'), $this->_dungeon->id));
    }

    public function broadcastAs()
    {
        return 'mapicon-deleted';
    }

    public function broadcastWith()
    {
        return [
            'id'   => $this->_id,
            'user' => $this->_user->name
        ];
    }
}
