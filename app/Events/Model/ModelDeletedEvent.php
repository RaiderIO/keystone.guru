<?php

namespace App\Events\Model;

use App\Events\ContextEvent;
use App\User;
use Illuminate\Database\Eloquent\Model;

class ModelDeletedEvent extends ContextEvent
{
    /** @var int $_modelId */
    protected int $_modelId;

    /** @var string $_modelClass */
    protected string $_modelClass;

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
        // Don't save Model here because serialization will fail due to object being deleted
        $this->_modelId    = $overpulledEnemy->getRouteKey();
        $this->_modelClass = get_class($overpulledEnemy);
        parent::__construct($context, $user);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'model_id'    => $this->_modelId,
            'model_class' => $this->_modelClass,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'model-deleted';
    }
}
