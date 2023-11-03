<?php


namespace App\Logic\MapContext;

use App\Models\Floor\Floor;
use App\Models\LiveSession;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Illuminate\Support\Facades\App;

/**
 * Class MapContextLiveSession
 * @package App\Logic\MapContext
 * @author  Wouter
 * @since   13/05/2021
 *
 * @property LiveSession $context
 */
class MapContextLiveSession extends MapContext
{
    use DungeonRouteProperties;

    private OverpulledEnemyServiceInterface $overpulledEnemyService;

    public function __construct(
        CacheServiceInterface           $cacheService,
        CoordinatesServiceInterface     $coordinatesService,
        OverpulledEnemyServiceInterface $overpulledEnemyService,
        LiveSession                     $liveSession,
        Floor                           $floor)
    {
        $this->overpulledEnemyService = $overpulledEnemyService;

        parent::__construct($cacheService, $coordinatesService, $liveSession, $floor, $liveSession->dungeonroute->mappingVersion);
    }

    public function getType(): string
    {
        return 'livesession';
    }

    public function isTeeming(): bool
    {
        return $this->context->dungeonroute->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->context->dungeonroute->seasonal_index;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->mappingVersion, false) ?? [];
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-live-session.%s', config('app.type'), $this->context->getRouteKey());
    }

    public function getProperties(): array
    {
        $routeCorrection = $this->overpulledEnemyService->getRouteCorrection($this->context);

        return array_merge(parent::getProperties(), $this->getDungeonRouteProperties($this->context->dungeonroute), [
            'liveSessionPublicKey' => $this->context->public_key,
            'expiresInSeconds'     => $this->context->getExpiresInSeconds(),
            'overpulledEnemies'    => $this->context->getEnemies()->pluck('id'),
            'obsoleteEnemies'      => $routeCorrection->getObsoleteEnemies(),
            'enemyForcesOverride'  => $routeCorrection->getEnemyForces(),
        ]);
    }

}
