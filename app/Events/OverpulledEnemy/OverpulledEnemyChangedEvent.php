<?php

namespace App\Events\OverpulledEnemy;

use App\Events\ContextEvent;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Lets broadcasts queued by the previous release unserialize after the deploy.
 *
 * @deprecated Use {@see \App\Events\LiveSession\OverpulledEnemy\OverpulledEnemyChangedEvent}. Removed one release after the move.
 */
class OverpulledEnemyChangedEvent extends ContextEvent
{
    protected int $enemy_id;

    protected int $kill_zone_id;

    public function __construct(Model $context, User $user, OverpulledEnemy $overpulledEnemy, Enemy $enemy)
    {
        $this->enemy_id     = $enemy->id;
        $this->kill_zone_id = $overpulledEnemy->kill_zone_id;
        parent::__construct($context, $user);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'enemy_id'     => $this->enemy_id,
            'kill_zone_id' => $this->kill_zone_id,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'overpulledenemy-changed';
    }
}
