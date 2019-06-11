<?php

namespace App\Events;

use App\Models\DungeonRoute;
use App\Models\MapComment;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapCommentDeletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var DungeonRoute $_dungeonroute */
    private $_dungeonroute;

    /** @var int $_id */
    private $_id;

    /** @var User $_user */
    private $_user;

    /**
     * Create a new event instance.
     *
     * @param $dungeonroute DungeonRoute
     * @param $mapComment MapComment
     * @return void
     */
    public function __construct(DungeonRoute $dungeonroute, MapComment $mapComment, User $user)
    {
        $this->_dungeonroute = $dungeonroute;
        $this->_id = $mapComment->id;
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
        return 'mapcomment-deleted';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->_id,
            'user' => $this->_user->name
        ];
    }
}
