<?php

namespace App\Events;

use App\Models\MapComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapCommentDeletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var int $_id */
    private $_id;

    /**
     * Create a new event instance.
     *
     * @param $mapComment MapComment
     * @return void
     */
    public function __construct(MapComment $mapComment)
    {
        $this->_id = $mapComment->id;
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
