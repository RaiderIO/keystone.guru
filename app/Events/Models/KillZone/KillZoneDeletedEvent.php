<?php

namespace App\Events\Models\KillZone;

use App\Events\Models\ModelDeletedEvent;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Illuminate\Database\Eloquent\Model;

class KillZoneDeletedEvent extends ModelDeletedEvent
{
    public function __construct(
        private readonly KillZonePathServiceInterface $killZonePathService,
        Model                                         $context,
        User                                          $user,
        private readonly KillZone                     $model,
        private readonly bool                         $recalculateKillZonePaths = true,
    ) {
        parent::__construct($context, $user, $model);
    }

    public function broadcastAs(): string
    {
        return 'killzone-deleted';
    }

    public function broadcastWith(): array
    {
        $parentResult     = parent::broadcastWith();
        $killZonePathData = $this->recalculateKillZonePaths ? $this->killZonePathService->calculateForRoute(
            $this->model->dungeonRoute,
            $this->model->dungeonRoute->mappingVersion->facade_enabled &&
            User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE,
        ) : null;

        return array_filter(
            array_merge(
                $parentResult,
                [
                    'killzone_paths' => $killZonePathData,
                ],
            ),
        );
    }
}
