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
     * @param Path|Model                  $model
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected Path|Model                         $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'path-changed';
    }

    #[\Override]
    public function broadcastWith(): array
    {
        /** @var Path $model */
        $model = $this->model;

        return array_merge(
            parent::broadcastWith(),
            [
                'model_data' => $model->polyline->getCoordinatesData(
                    $this->coordinatesService,
                    $model->dungeonRoute->mappingVersion,
                    $model->floor,
                ),
            ],
        );
    }
}
