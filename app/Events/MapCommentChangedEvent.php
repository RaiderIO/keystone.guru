<?php

namespace App\Events;

use App\Models\MapComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapCommentChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var MapComment $_mapComment */
    private $_mapComment;

    /**
     * Create a new event instance.
     *
     * @param $mapComment MapComment
     * @return void
     */
    public function __construct(MapComment $mapComment)
    {
        $this->_mapComment = $mapComment;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('route-edit');
    }

    public function broadcastWith()
    {
        return [
            'mapcomment' => $this->_mapComment
        ];
    }
}
