<?php

namespace App\Events\Model;

use App\Events\ContextEvent;
use App\Models\Interfaces\EventModelInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class ContextModelEvent extends ContextEvent
{
    public function __construct(Model $context, User $user, protected EventModelInterface $model)
    {
        parent::__construct($context, $user);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'model'       => $this->model,
            'model_class' => $this->model::class,
            'model_data'        => $this->model->getEventData(),
        ]);
    }
}
