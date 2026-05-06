<?php

namespace App\Events\Models;

use App\Events\ContextEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class ModelDeletedEvent extends ContextEvent
{
    protected int $modelId;

    protected string $modelClass;

    public function __construct(Model $context, User $user, Model $model)
    {
        // Don't save Model here because serialization will fail due to object being deleted
        $this->modelId    = $model->getRouteKey();
        $this->modelClass = $model::class;
        parent::__construct($context, $user);
    }

    #[\Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'model_id'    => $this->modelId,
            'model_class' => $this->modelClass,
        ]);
    }
}
