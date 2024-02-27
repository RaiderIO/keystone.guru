<?php

namespace App\Events\OverpulledEnemy;

use App\Events\ContextEvent;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\User;
use Illuminate\Database\Eloquent\Model;

class OverpulledEnemyDeletedEvent extends ContextEvent
{
    protected int $enemy_id;

    public function __construct(Model $context, User $user, OverpulledEnemy $overpulledEnemy, Enemy $enemy)
    {
        // Don't save Model here because serialization will fail due to object being deleted
        $this->enemy_id = $enemy->id;
        parent::__construct($context, $user);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'enemy_id' => $this->enemy_id,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'overpulledenemy-deleted';
    }
}
