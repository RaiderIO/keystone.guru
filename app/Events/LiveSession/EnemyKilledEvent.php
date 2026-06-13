<?php

namespace App\Events\LiveSession;

use App\Events\ContextEvent;
use App\Models\Enemy;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

class EnemyKilledEvent extends ContextEvent
{
    protected int $enemy_id;

    public function __construct(Model $context, User $user, Enemy $enemy)
    {
        $this->enemy_id = $enemy->id;
        parent::__construct($context, $user);
    }

    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'enemy_id' => $this->enemy_id,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'enemy-killed';
    }
}
