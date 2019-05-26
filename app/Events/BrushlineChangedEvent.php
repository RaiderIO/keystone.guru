<?php

namespace App\Events;

use App\Models\Brushline;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrushlineChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var Brushline $_brushline */
    private $_brushline;

    /**
     * Create a new event instance.
     *
     * @param $brushline Brushline
     * @return void
     */
    public function __construct(Brushline $brushline)
    {
        $this->_brushline = $brushline;
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
        return 'brushline-changed';
    }

    public function broadcastWith()
    {
        return [
            'brushline' => $this->_brushline
        ];
    }
}
