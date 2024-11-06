<?php

namespace App\Events\Models\Enemy;

use App\Events\Models\ModelChangedEvent;
use App\Models\Enemy;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Enemy $model
 */
class EnemyChangedEvent extends ModelChangedEvent
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
        protected Enemy|Model                        $model
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'enemy-changed';
    }

    public function broadcastWith(): array
    {
        return array_merge(
            parent::broadcastWith(), [
                'model_data' => $this->model->getCoordinatesData($this->coordinatesService),
            ]
        );
    }
}
