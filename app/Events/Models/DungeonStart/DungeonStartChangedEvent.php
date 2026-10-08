<?php

namespace App\Events\Models\DungeonStart;

use App\Events\Models\ModelChangedEvent;
use App\Models\DungeonStart;
use Override;

/**
 * @property DungeonStart $model
 */
class DungeonStartChangedEvent extends ModelChangedEvent
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'model' => $this->model->append(DungeonStart::DESTINATION_ATTRIBUTES),
        ]);
    }

    public function broadcastAs(): string
    {
        return 'dungeonstart-changed';
    }
}
