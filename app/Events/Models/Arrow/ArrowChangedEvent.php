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
