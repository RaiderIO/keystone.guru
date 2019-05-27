<?php

namespace App\Events;

use App\Models\Brushline;
use App\Models\DungeonRoute;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrushlineChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var DungeonRoute $_dungeonroute */
    private $_dungeonroute;

    /** @var Brushline $_brushline */
    private $_brushline;

    /**
     * Create a new event instance.
     *
     * @param $dungeonroute DungeonRoute
     * @param $brushline Brushline
     * @return void
     */
    public function __construct(DungeonRoute $dungeonroute, Brushline $brushline)
    {
        $this->_dungeonroute = $dungeonroute;
        $this->_brushline = $brushline;
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
        return 'brushline-changed';
    }

    public function broadcastWith()
    {
        return [
            'brushline' => $this->_brushline
        ];
    }
}
