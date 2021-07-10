<?php

namespace App\Events\Model;

use App\Events\ContextEvent;
use App\User;
use Illuminate\Database\Eloquent\Model;

abstract class ContextModelEvent extends ContextEvent
{
    /** @var Model $_model */
    protected Model $_model;

    /**
     * Create a new event instance.
     *
     * @param $context Model
     * @param $user User
     * @param $overpulledEnemy Model
     * @return void
     */
    public function __construct(Model $context, User $user, Model $overpulledEnemy)
    {
        $this->_model = $overpulledEnemy;
        parent::__construct($context, $user);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'model'       => $this->_model,
            'model_class' => get_class($this->_model),
        ]);
    }
}
