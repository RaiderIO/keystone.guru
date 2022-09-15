<?php

namespace App\Events\LiveSession;

use App\Events\ContextEvent;
use App\Models\LiveSession;
use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class InviteEvent
 * @package App\Events\LiveSession
 * @author Wouter
 * @since 14/05/2021
 *
 * @property LiveSession $_context
 */
class InviteEvent extends ContextEvent
{
    /** @var array */
    protected array $invitees;

    /**
     * Create a new event instance.
     *
     * @param LiveSession $liveSession
     * @param User $user
     * @param Collection $invitees
     * @return void
     */
    public function __construct(LiveSession $liveSession, User $user, Collection $invitees)
    {
        parent::__construct($liveSession, $user);

        $this->invitees = $invitees->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel(sprintf('%s-route-edit.%s', config('app.type'), $this->_context->dungeonroute->getRouteKey())),
        ];
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            // Cannot use ContextModelEvent as model is already deleted and serialization will fail
            'invitees' => $this->invitees,
            'url'      => route('dungeonroute.livesession.view', [
                'dungeon'      => $this->_context->dungeonroute->dungeon,
                'title'        => Str::slug($this->_context->dungeonroute->title),
                'dungeonroute' => $this->_context->dungeonroute,
                'livesession'  => $this->_context,
            ]),
        ]);
    }

    public function broadcastAs(): string
    {
        return 'livesession-invite';
    }
}
