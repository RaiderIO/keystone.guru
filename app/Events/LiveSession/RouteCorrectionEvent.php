<?php

namespace App\Events\LiveSession;

use App\Events\ContextEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

class RouteCorrectionEvent extends ContextEvent
{
    /**
     * @param array<int, int> $enemy_ids
     */
    public function __construct(
        Model           $context,
        User            $user,
        protected array $enemy_ids,
    ) {
        parent::__construct($context, $user);
    }

    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'enemy_ids' => $this->enemy_ids,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'route-correction';
    }
}
