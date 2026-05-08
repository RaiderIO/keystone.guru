<?php

namespace App\Events\Models\KillZone;

use App\Events\Models\ModelChangedEvent;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Illuminate\Database\Eloquent\Model;

class KillZoneChangedEvent extends ModelChangedEvent
{
    public function __construct(
        private readonly KillZonePathServiceInterface $killZonePathService,
        private readonly CoordinatesServiceInterface  $coordinatesService,
        Model                                         $context,
        User                                          $user,
        protected KillZone|Model                      $model,
        private readonly bool                         $recalculateKillZonePaths = true,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'killzone-changed';
    }

    #[\Override]
    public function broadcastWith(): array
    {
        $parentResult     = parent::broadcastWith();
        $killZonePathData = $this->recalculateKillZonePaths ? $this->killZonePathService->calculateForRoute(
            $this->model->dungeonRoute,
            $this->model->dungeonRoute->mappingVersion->facade_enabled &&
            User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE,
        ) : null;

        if ($this->model->floor_id === null) {
            return array_filter(
                array_merge($parentResult, [
                    'killzone_paths' => $killZonePathData,
                ]),
            );
        }

        return array_merge(
            $parentResult,
            [
                'model_data'     => $this->model->getCoordinatesData($this->coordinatesService),
                'killzone_paths' => $this->killZonePathService->calculateForRoute(
                    $this->model->dungeonRoute,
                    $this->model->dungeonRoute->mappingVersion->facade_enabled &&
                        User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE,
                ),
            ],
        );
    }
}
