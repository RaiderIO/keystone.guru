<?php

namespace App\Events\Models\KillZone;

use App\Events\Models\ModelChangedEvent;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Model;

class KillZoneChangedEvent extends ModelChangedEvent
{
    /**
     * @param CoordinatesServiceInterface $coordinatesService
     * @param Model                       $context
     * @param User                        $user
     * @param KillZone|Model              $model
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        Model                                        $context,
        User                                         $user,
        protected KillZone|Model                     $model,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'killzone-changed';
    }

    public function broadcastWith(): array
    {
        $parentResult = parent::broadcastWith();
        if ($this->model->floor_id === null) {
            return $parentResult;
        }

        return array_merge(
            $parentResult,
            [
                'model_data' => $this->model->getCoordinatesData($this->coordinatesService),
            ],
        );
    }
}
