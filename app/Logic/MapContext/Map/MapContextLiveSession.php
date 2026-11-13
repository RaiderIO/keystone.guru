<?php

namespace App\Logic\MapContext\Map;

use App\Models\LiveSession\LiveSession;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\LiveSession\LiveSessionCombatStateServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Override;

/**
 * Class MapContextLiveSession
 *
 * @author  Wouter
 *
 * @since   13/05/2021
 */
class MapContextLiveSession extends MapContextDungeonRoute
{
    public function __construct(
        CacheServiceInterface                                   $cacheService,
        CoordinatesServiceInterface                             $coordinatesService,
        KillZonePathServiceInterface                            $killZonePathService,
        private readonly OverpulledEnemyServiceInterface        $overpulledEnemyService,
        private readonly LiveSessionCombatStateServiceInterface $combatStateService,
        private readonly LiveSession                            $liveSession,
        string                                                  $mapFacadeStyle,
    ) {
        parent::__construct($cacheService, $coordinatesService, $killZonePathService, $this->liveSession->dungeonRoute, $mapFacadeStyle);
    }

    #[Override]
    public function getType(): string
    {
        return 'livesession';
    }

    #[Override]
    public function getEchoChannelName(): string
    {
        return sprintf('%s-live-session.%s', config('app.type'), $this->liveSession->getRouteKey());
    }

    #[Override]
    public function toArray(): array
    {
        $routeCorrection = $this->overpulledEnemyService->getRouteCorrection($this->liveSession);
        $useFacade       = $this->mapFacadeStyle === User::MAP_FACADE_STYLE_FACADE;

        return array_merge(parent::toArray(), [
            'liveSessionPublicKey' => $this->liveSession->public_key,
            'expiresInSeconds'     => $this->liveSession->getExpiresInSeconds(),
            'overpulledEnemies'    => $this->liveSession->mapContextOverpulledEnemies(),
            'obsoleteEnemies'      => $routeCorrection->getObsoleteEnemies()
                ->merge($this->combatStateService->getObsoleteEnemyIds($this->liveSession))
                ->unique()
                ->values(),
            'enemyForcesOverride' => $routeCorrection->getEnemyForces(),
            'killedEnemies'       => $this->liveSession->mapContextKilledEnemyIds(),
            'inCombatEnemies'     => $this->liveSession->mapContextInCombatEnemyIds(),
            'playerPositions'     => $this->liveSession->mapContextPlayerPositions($this->coordinatesService, $useFacade),
        ]);
    }
}
