<?php

namespace App\Events\Models\Brushline;

use App\Events\Models\ModelChangedEvent;
use App\Models\Brushline;
use App\Models\MapIcon;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Override;

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

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        /** @var Brushline $model */
        $model = $this->model;

        $broadcast = parent::broadcastWith();

        // The receiving client always overwrites model.polyline.vertices_json with the
        // model_data coordinates below (see ModelChangedHandler::_getCorrectLatLngFromEvent()
        // and Brushline's changed.js), so broadcasting the raw vertices here too is dead weight
        // that can push large brushlines over Reverb's message size cap (#3909).
        $modelArray = $model->toArray();
        unset($modelArray['polyline']['vertices_json']);
        $broadcast['model'] = $modelArray;

        return array_merge(
            $broadcast,
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
