<?php


namespace App\Logic\MapContext;

use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Models\DungeonRoute;
use App\Models\Floor;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class MapContextDungeonRoute
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 06/08/2020
 *
 * @property DungeonRoute $context
 */
class MapContextDungeonRoute extends MapContext
{
    use DungeonRouteProperties;

    public function __construct(DungeonRoute $dungeonRoute, Floor $floor)
    {
        parent::__construct($dungeonRoute, $floor, $dungeonRoute->mappingVersion);
    }

    public function getType(): string
    {
        return 'dungeonroute';
    }

    public function isTeeming(): bool
    {
        return $this->context->teeming;
    }

    /**
     * @throws InvalidMDTDungeonException
     * @throws InvalidArgumentException
     */
    public function getEnemies(): array
    {
        return $this->listEnemies($this->mappingVersion);
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-route-edit.%s', config('app.type'), $this->context->getRouteKey());
    }

    public function getProperties(): array
    {
        return array_merge(parent::getProperties(),
            $this->getDungeonRoutesProperties(
                collect([$this->context])
            )->toArray()
        );
    }
}
