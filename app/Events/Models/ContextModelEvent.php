<?php

namespace App\Events\Models;

use App\Events\ContextEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class ContextModelEvent extends ContextEvent
{
    public function __construct(Model $context, User $user, protected Model $model)
    {
        parent::__construct($context, $user);
    }

    public function broadcastWith(): array
    {
        dd(array_merge(parent::broadcastWith(), [
            'model'       => $this->model,
            'model_class' => $this->model::class,
        ]));
        return array_merge(parent::broadcastWith(), [
            'model'       => $this->model,
            'model_class' => $this->model::class,
        ]);
    }
}
