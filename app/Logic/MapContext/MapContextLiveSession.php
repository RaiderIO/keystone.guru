<?php


namespace App\Logic\MapContext;

use App\Models\Floor;
use App\Models\LiveSession;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Illuminate\Support\Facades\App;

/**
 * Class MapContextLiveSession
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 13/05/2021
 *
 * @property LiveSession $context
 */
class MapContextLiveSession extends MapContext
{
    use DungeonRouteProperties;

    public function __construct(LiveSession $liveSession, Floor $floor)
    {
        parent::__construct($liveSession, $floor, $liveSession->dungeonRoute->mappingVersion);
    }

    public function getType(): string
    {
        return 'livesession';
    }

    public function isTeeming(): bool
    {
        return $this->context->dungeonRoute->teeming;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->mappingVersion, false);
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-live-session.%s', config('app.type'), $this->context->getRouteKey());
    }

    public function getProperties(): array
    {
        /** @var OverpulledEnemyServiceInterface $overpulledEnemyService */
        $overpulledEnemyService = App::make(OverpulledEnemyServiceInterface::class);

        $routeCorrection = $overpulledEnemyService->getRouteCorrection($this->context);

        return array_merge(parent::getProperties(),
            $this->getDungeonRoutesProperties(
                collect([$this->context->dungeonRoute])
            )->toArray(), [
                'liveSessionPublicKey' => $this->context->public_key,
                'expiresInSeconds'     => $this->context->getExpiresInSeconds(),
                'overpulledEnemies'    => $this->context->getEnemies()->pluck('id'),
                'obsoleteEnemies'      => $routeCorrection->getObsoleteEnemies(),
                'enemyForcesOverride'  => $routeCorrection->getEnemyForces(),
            ]);
    }

}
