<?php

namespace App\Events\Model;

use App\Events\ContextEvent;
use App\User;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

class ModelDeletedEvent extends ContextEvent
{
    /** @var int $modelId */
    protected int $modelId;

    /** @var string $modelClass */
    protected string $modelClass;

    /** @var string $modelName */
    private string $modelName;

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
        // Don't save Model here because serialization will fail due to object being deleted
        $this->modelId    = $model->getRouteKey();
        $this->modelClass = get_class($model);
        $this->modelName  = strtolower((new ReflectionClass($model))->getShortName());
        parent::__construct($context, $user);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'model_id'    => $this->modelId,
            'model_class' => $this->modelClass,
        ]);
    }

    public function broadcastAs(): string
    {
        return sprintf('%s-deleted', $this->modelName);
    }
}
