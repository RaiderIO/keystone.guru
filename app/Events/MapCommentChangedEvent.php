<?php

namespace App\Events;

use App\Models\DungeonRoute;
use App\Models\MapComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapCommentChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var DungeonRoute $_dungeonroute */
    private $_dungeonroute;

    /** @var MapComment $_mapComment */
    private $_mapComment;

    /**
     * Create a new event instance.
     *
     * @param $dungeonroute DungeonRoute
     * @param $mapComment MapComment
     * @return void
     */
    public function __construct(DungeonRoute $dungeonroute, MapComment $mapComment)
    {
        $this->_dungeonroute = $dungeonroute;
        $this->_mapComment = $mapComment;
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
        return 'mapcomment-changed';
    }

    public function broadcastWith()
    {
        return [
            'mapcomment' => $this->_mapComment
        ];
    }
}
