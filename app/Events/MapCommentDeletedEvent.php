<?php

namespace App\Events;

use App\Models\DungeonRoute;
use App\Models\MapComment;
use Illuminate\Broadcasting\InteractsWithSockets;
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
        $this->_id = $mapComment->id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel(sprintf('route-edit.%s', $this->_dungeonroute->public_key));
    }

    public function broadcastAs()
    {
        return 'mapcomment-deleted';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->_id
        ];
    }
}
