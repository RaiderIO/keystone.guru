<?php

namespace App\Events\Models\Arrow;

use App\Events\Models\ModelChangedEvent;
use App\Models\Arrow;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property Arrow $model
 */
class ArrowChangedEvent extends ModelChangedEvent
{
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected Arrow|Model                        $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'arrow-changed';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function broadcastWith(): array
    {
        /** @var Arrow $model */
        $model = $this->model;

        $broadcast = parent::broadcastWith();

        // The receiving client always overwrites model.polyline.vertices_json with the
        // model_data coordinates below (see ModelChangedHandler::_getCorrectLatLngFromEvent()
        // and Arrow's changed.js), so broadcasting the raw vertices here too is dead weight
        // that can push large arrows over Reverb's message size cap (#3909).
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
