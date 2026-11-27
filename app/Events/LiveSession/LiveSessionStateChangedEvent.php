<?php

namespace App\Events\LiveSession;

use App\Events\ContextEvent;
use App\Models\User;
use App\Service\LiveSession\LiveSessionStateServiceInterface;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Carries the complete live state of a live session, so a viewer that missed an earlier broadcast is whole again
 * on the next one. Broadcast synchronously: the sender only records the state as delivered once Reverb took it.
 *
 * @phpstan-import-type LiveSessionState from LiveSessionStateServiceInterface
 */
class LiveSessionStateChangedEvent extends ContextEvent implements ShouldBroadcastNow
{
    /**
     * @param LiveSessionState $state
     */
    public function __construct(
        Model           $context,
        User            $user,
        protected array $state,
    ) {
        parent::__construct($context, $user);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'state' => $this->state,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'livesession-state';
    }
}
