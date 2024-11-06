<?php

namespace App\Events\Models\MapIcon;

use App\Events\Models\ModelChangedEvent;
use App\Models\MapIcon;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property MapIcon $model
 */
class MapIconChangedEvent extends ModelChangedEvent
{
    /**
     * @param CoordinatesServiceInterface $coordinatesService
     * @param Model                       $context
     * @param User                        $user
     * @param MapIcon|Model               $model
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected MapIcon|Model                      $model
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'mapicon-changed';
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
