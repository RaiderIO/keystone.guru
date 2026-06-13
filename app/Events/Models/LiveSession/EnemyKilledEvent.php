<?php

namespace App\Events\Models\LiveSession;

use App\Events\Models\ModelChangedEvent;
use App\Models\Enemy;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property Enemy $model
 */
class EnemyKilledEvent extends ModelChangedEvent
{
    /**
     * @param CoordinatesServiceInterface $coordinatesService
     * @param Model                       $context
     * @param User                        $user
     * @param Enemy|Model                 $model
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected Enemy|Model                        $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'enemy-killed';
    }

    #[Override]
    public function broadcastWith(): array
    {
        /** @var Enemy $model */
        $model     = $this->model;
        $modelData = $model->getCoordinatesData($this->coordinatesService);

        // Unset deeply-nested relations before serialization to prevent circular
        // references in the dungeon model graph from causing JSON encoding to fail.
        $model->unsetRelation('floor');
        $model->unsetRelation('mappingVersion');
        $model->unsetRelation('enemyPack');
        $model->unsetRelation('enemyPatrol');

        return array_merge(
            parent::broadcastWith(),
            [
                'model_data' => $modelData,
            ],
        );
    }
}
