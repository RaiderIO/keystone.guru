<?php

namespace App\Events\Models\Npc;

use App\Events\Models\ModelChangedEvent;
use App\Models\Npc\Npc;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property Npc $model
 */
class NpcChangedEvent extends ModelChangedEvent
{
    public function __construct(
        Model                 $context,
        User                  $user,
        Model                 $model,
        private readonly bool $npcRemovedFromDungeon = false,
        private readonly ?int $oldNpcId = null,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'npc-changed';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'npc_removed_from_dungeon' => $this->npcRemovedFromDungeon,
            'old_npc_id'               => $this->oldNpcId,
        ]);
    }
}
