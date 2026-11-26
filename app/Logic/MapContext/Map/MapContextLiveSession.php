<?php

namespace App\Logic\MapContext\Map;

use App\Models\LiveSession\LiveSession;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\LiveSession\LiveSessionStateServiceInterface;
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
        CacheServiceInterface                             $cacheService,
        CoordinatesServiceInterface                       $coordinatesService,
        KillZonePathServiceInterface                      $killZonePathService,
        private readonly LiveSessionStateServiceInterface $liveSessionStateService,
        private readonly LiveSession                      $liveSession,
        string                                            $mapFacadeStyle,
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
        return array_merge(parent::toArray(), [
            'liveSessionPublicKey' => $this->liveSession->public_key,
            'expiresInSeconds'     => $this->liveSession->getExpiresInSeconds(),
            'liveSessionState'     => $this->liveSessionStateService->getState($this->liveSession),
        ]);
    }
}
