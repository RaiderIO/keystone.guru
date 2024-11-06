<?php

namespace App\Events\Models\Path;

use App\Events\Models\ModelChangedEvent;
use App\Models\MapIcon;
use App\Models\Path;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property MapIcon $model
 */
class PathChangedEvent extends ModelChangedEvent
{
    /**
     * @param CoordinatesServiceInterface $coordinatesService
     * @param Model                       $context
     * @param User                        $user
     * @param Path|Model             $model
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected Path|Model                    $model
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'path-changed';
    }

    public function broadcastWith(): array
    {
        return array_merge(
            parent::broadcastWith(), [
                'model_data' => $this->model->polyline->getCoordinatesData(
                    $this->coordinatesService,
                    $this->model->dungeonRoute->mappingVersion,
                    $this->model->floor
                ),
            ]
        );
    }
}
