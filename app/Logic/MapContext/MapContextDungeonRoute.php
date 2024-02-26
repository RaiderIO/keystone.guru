<?php

namespace App\Logic\MapContext;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;

/**
 * Class MapContextDungeonRoute
 *
 * @author  Wouter
 *
 * @since   06/08/2020
 *
 * @property DungeonRoute $context
 */
class MapContextDungeonRoute extends MapContext
{
    use DungeonRouteProperties;

    public function __construct(
        CacheServiceInterface $cacheService,
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute $dungeonRoute,
        Floor $floor,
        ?string $mapFacadeStyle = null
    ) {
        parent::__construct($cacheService, $coordinatesService, $dungeonRoute, $floor, $dungeonRoute->mappingVersion, $mapFacadeStyle);
    }

    public function getType(): string
    {
        return 'dungeonroute';
    }

    public function isTeeming(): bool
    {
        return $this->context->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->context->seasonal_index;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->cacheService, $this->coordinatesService, $this->mappingVersion, false) ?? [];
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-route-edit.%s', config('app.type'), $this->context->getRouteKey());
    }

    public function getProperties(): array
    {
        return array_merge(parent::getProperties(), $this->getDungeonRouteProperties($this->coordinatesService, $this->context));
    }
}
