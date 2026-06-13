<?php

namespace App\Events\Models\LiveSession;

use App\Events\Models\ModelChangedEvent;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property LiveSessionPlayerPosition $model
 */
class PlayerMovedEvent extends ModelChangedEvent
{
    /**
     * @param CoordinatesServiceInterface     $coordinatesService
     * @param Model                           $context
     * @param User                            $user
     * @param LiveSessionPlayerPosition|Model $model
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected LiveSessionPlayerPosition|Model    $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'player-moved';
    }

    #[Override]
    public function broadcastWith(): array
    {
        /** @var LiveSessionPlayerPosition $model */
        $model     = $this->model;
        $modelData = $model->getCoordinatesData($this->coordinatesService);

        // Unset deeply-nested relations before serialization to prevent circular
        // references in the dungeon model graph from causing JSON encoding to fail.
        $model->unsetRelation('liveSession');
        $model->unsetRelation('floor');

        return array_merge(
            parent::broadcastWith(),
            [
                'player_guid'    => $model->player_guid,
                'character_name' => $model->character_name,
                'model_data'     => $modelData,
            ],
        );
    }
}
