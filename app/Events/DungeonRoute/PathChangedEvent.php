<?php

namespace App\Events\DungeonRoute;

use App\Models\DungeonRoute;
use App\Models\Path;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PathChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var DungeonRoute $_dungeonroute */
    private $_dungeonroute;

    /** @var Path $_path */
    private $_path;

    /** @var User $_user */
    private $_user;

    /**
     * Create a new event instance.
     *
     * @param $dungeonroute DungeonRoute
     * @param $path Path
     * @return void
     */
    public function __construct(DungeonRoute $dungeonroute, Path $path, User $user)
    {
        $this->_dungeonroute = $dungeonroute;
        $this->_path = $path;
        $this->_user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel(sprintf('%s-route-edit.%s', env('APP_TYPE'), $this->_dungeonroute->public_key));
    }

    public function broadcastAs()
    {
        return 'path-changed';
    }

    public function broadcastWith()
    {
        return [
            'path' => $this->_path,
            'user' => $this->_user->name
        ];
    }
}
