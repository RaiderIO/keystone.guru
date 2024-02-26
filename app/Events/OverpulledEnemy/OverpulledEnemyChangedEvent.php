<?php

namespace App\Events\OverpulledEnemy;

use App\Events\ContextEvent;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\User;
use Illuminate\Database\Eloquent\Model;

class OverpulledEnemyChangedEvent extends ContextEvent
{
    protected int $enemy_id;

    protected int $kill_zone_id;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $context, User $user, OverpulledEnemy $overpulledEnemy, Enemy $enemy)
    {
        $this->enemy_id = $enemy->id;
        $this->kill_zone_id = $overpulledEnemy->kill_zone_id;
        parent::__construct($context, $user);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'enemy_id' => $this->enemy_id,
            'kill_zone_id' => $this->kill_zone_id,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'overpulledenemy-changed';
    }
}
