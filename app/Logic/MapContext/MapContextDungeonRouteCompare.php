<?php


namespace App\Logic\MapContext;

use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\LiveSession;
use App\Models\Mapping\MappingVersion;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * Class MapContextDungeonRouteCompare
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 13/05/2021
 *
 * @property LiveSession $context
 */
class MapContextDungeonRouteCompare extends MapContext
{
    use DungeonRouteProperties;

    /** @var Collection|DungeonRoute[] */
    private Collection $dungeonRoutes;

    public function __construct(Collection $dungeonRoutes, MappingVersion $mappingVersion, Floor $floor)
    {
        $this->dungeonRoutes = $dungeonRoutes;

        parent::__construct($mappingVersion, $floor, $mappingVersion);
    }

    public function getType(): string
    {
        return 'dungeonRouteCompare';
    }

    public function isTeeming(): bool
    {
        return $this->getDungeonRoute()->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->getDungeonRoute()->seasonal_index;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->mappingVersion, false);
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-route-compare.%s', config('app.type'), $this->dungeonRoutes->pluck(['public_key'])->implode('-'));
    }

    public function getProperties(): array
    {
        /** @var OverpulledEnemyServiceInterface $overpulledEnemyService */
        $overpulledEnemyService = App::make(OverpulledEnemyServiceInterface::class);

        $routeCorrection = $overpulledEnemyService->getRouteCorrection($this->context);

        return array_merge(parent::getProperties(), $this->getDungeonRouteProperties($this->context->dungeonroute), [
            'liveSessionPublicKey' => $this->context->public_key,
            'expiresInSeconds'     => $this->context->getExpiresInSeconds(),
            'overpulledEnemies'    => $this->context->getEnemies()->pluck('id'),
            'obsoleteEnemies'      => $routeCorrection->getObsoleteEnemies(),
            'enemyForcesOverride'  => $routeCorrection->getEnemyForces(),
        ]);
    }

    /**
     * @return DungeonRoute
     */
    private function getDungeonRoute(): DungeonRoute
    {
        return $this->dungeonRoutes->first();
    }

}
