<?php

namespace App\Events\Dungeon;

use App\Models\Dungeon;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModelDeletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var Dungeon $_dungeon */
    private Dungeon $_dungeon;

    /** @var Model $_model */
    private Model $_model;

    /** @var User $_user */
    private User $_user;

    /**
     * Create a new event instance.
     *
     * @param $dungeon Dungeon
     * @param $model Model
     * @param $user User
     * @return void
     */
    public function __construct(Dungeon $dungeon, Model $model, User $user)
    {
        $this->_dungeon = $dungeon;
        $this->_model = $model;
        $this->_user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel(sprintf('%s-dungeon-edit.%s', env('APP_TYPE'), $this->_dungeon->id));
    }

    public function broadcastAs()
    {
        return 'model-deleted';
    }

    public function broadcastWith()
    {
        return [
            'id'    => $this->_model->id,
            'class' => get_class($this->_model),
            'user'  => $this->_user->name
        ];
    }
}
