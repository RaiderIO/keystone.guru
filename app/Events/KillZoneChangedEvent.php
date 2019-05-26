<?php

namespace App\Events;

use App\Models\KillZone;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KillZoneChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $_killZone;

    /**
     * Create a new event instance.
     *
     * @param $killZone KillZone
     * @return void
     */
    public function __construct(KillZone $killZone)
    {
        $this->_killZone = $killZone;
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
        return 'killzone-changed';
    }

    public function broadcastWith()
    {
        $this->_killZone->load('killzoneenemies');
        return [
            'killzone' => $this->_killZone
        ];
    }
}
