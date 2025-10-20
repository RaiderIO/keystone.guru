<?php

namespace App\Logic\MapContext;

use App\Models\Floor\Floor;
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
 *
 * @property LiveSession $context
 */
class MapContextLiveSession extends MapContext
{
    use DungeonRouteProperties;

    public function __construct(
        CacheServiceInterface                            $cacheService,
        CoordinatesServiceInterface                      $coordinatesService,
        private readonly OverpulledEnemyServiceInterface $overpulledEnemyService,
        LiveSession                                      $liveSession,
    ) {
        parent::__construct($cacheService, $coordinatesService, $liveSession, $liveSession->dungeonRoute->dungeon, $liveSession->dungeonRoute->mappingVersion);
    }

    public function getType(): string
    {
        return 'livesession';
    }

    public function isTeeming(): bool
    {
        return $this->context->dungeonRoute->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->context->dungeonRoute->seasonal_index;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->cacheService, $this->coordinatesService, $this->mappingVersion, false) ?? [];
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-live-session.%s', config('app.type'), $this->context->getRouteKey());
    }

    public function getProperties(): array
    {
        $routeCorrection = $this->overpulledEnemyService->getRouteCorrection($this->context);

        return array_merge(parent::getProperties(), $this->getDungeonRouteProperties(
            $this->coordinatesService,
            $this->context->dungeonRoute,
        ), [
            'liveSessionPublicKey' => $this->context->public_key,
            'expiresInSeconds'     => $this->context->getExpiresInSeconds(),
            'overpulledEnemies'    => $this->context->getEnemies()->pluck('id'),
            'obsoleteEnemies'      => $routeCorrection->getObsoleteEnemies(),
            'enemyForcesOverride'  => $routeCorrection->getEnemyForces(),
        ]);
    }
}
