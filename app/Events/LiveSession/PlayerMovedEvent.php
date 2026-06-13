<?php

namespace App\Events\LiveSession;

use App\Events\ContextEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

class PlayerMovedEvent extends ContextEvent
{
    public function __construct(
        Model            $context,
        User             $user,
        protected string $player_guid,
        protected string $character_name,
        protected float  $lat,
        protected float  $lng,
        protected int    $floor_id,
    ) {
        parent::__construct($context, $user);
    }

    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'player_guid'    => $this->player_guid,
            'character_name' => $this->character_name,
            'lat'            => $this->lat,
            'lng'            => $this->lng,
            'floor_id'       => $this->floor_id,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'player-moved';
    }
}
