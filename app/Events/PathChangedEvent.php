<?php

namespace App\Events;

use App\Models\KillZone;
use App\Models\Path;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PathChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var Path $_path */
    private $_path;

    /**
     * Create a new event instance.
     *
     * @param $path Path
     * @return void
     */
    public function __construct(Path $path)
    {
        $this->_path = $path;
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
            'path' => $this->_path
        ];
    }
}
