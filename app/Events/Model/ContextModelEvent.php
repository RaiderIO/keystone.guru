<?php

namespace App\Events\Model;

use App\Events\ContextEvent;
use App\User;
use Illuminate\Database\Eloquent\Model;

abstract class ContextModelEvent extends ContextEvent
{
    /** @var Model $model */
    protected Model $model;

    /**
     * Create a new event instance.
     *
     * @param $context Model
     * @param $user User
     * @param $model Model
     * @return void
     */
    public function __construct(Model $context, User $user, Model $model)
    {
        $this->model = $model;
        parent::__construct($context, $user);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'model'       => $this->model,
            'model_class' => get_class($this->model),
        ]);
    }
}
