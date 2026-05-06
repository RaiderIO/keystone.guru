<?php

namespace App\Logic\MapContext\Map;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\LiveSession;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;

/**
 * Class MapContextLiveSession
 *
 * @author  Wouter
 *
 * @since   13/05/2021
 */
class MapContextLiveSession extends MapContextDungeonRoute
{
    use ListsEnemies;

    public function __construct(
        CacheServiceInterface                            $cacheService,
        CoordinatesServiceInterface                      $coordinatesService,
        private readonly OverpulledEnemyServiceInterface $overpulledEnemyService,
        private readonly LiveSession                     $liveSession,
        string                                           $mapFacadeStyle,
    ) {
        parent::__construct($cacheService, $coordinatesService, $this->liveSession->dungeonRoute, $mapFacadeStyle);
    }

    #[\Override]
    public function getType(): string
    {
        return 'livesession';
    }

    #[\Override]
    public function getEchoChannelName(): string
    {
        return sprintf('%s-live-session.%s', config('app.type'), $this->liveSession->getRouteKey());
    }

    #[\Override]
    public function toArray(): array
    {
        $routeCorrection = $this->overpulledEnemyService->getRouteCorrection($this->liveSession);

        return array_merge(parent::toArray(), [
            'liveSessionPublicKey' => $this->liveSession->public_key,
            'expiresInSeconds'     => $this->liveSession->getExpiresInSeconds(),
            'overpulledEnemies'    => $this->liveSession->getEnemies()->pluck('id'),
            'obsoleteEnemies'      => $routeCorrection->getObsoleteEnemies(),
            'enemyForcesOverride'  => $routeCorrection->getEnemyForces(),
        ]);
    }
}
