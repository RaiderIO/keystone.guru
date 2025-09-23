<?php

namespace App\Events\Models\Brushline;

use App\Events\Models\ModelChangedEvent;
use App\Models\Brushline;
use App\Models\MapIcon;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property MapIcon $model
 */
class BrushlineChangedEvent extends ModelChangedEvent
{
    /**
     * @param CoordinatesServiceInterface $coordinatesService
     * @param Model                       $context
     * @param User                        $user
     * @param Brushline|Model             $model
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected Brushline|Model                    $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'brushline-changed';
    }

    public function broadcastWith(): array
    {
        return array_merge(
            parent::broadcastWith(),
            [
                'model_data' => $this->model->polyline->getCoordinatesData(
                    $this->coordinatesService,
                    $this->model->dungeonRoute->mappingVersion,
                    $this->model->floor,
                ),
            ],
        );
    }
}
