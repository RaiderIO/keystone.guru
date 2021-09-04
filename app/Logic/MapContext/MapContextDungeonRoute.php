<?php


namespace App\Logic\MapContext;

use App\Models\DungeonRoute;
use App\Models\Floor;

/**
 * Class MapContextDungeonRoute
 * @package App\Logic\MapContext
 * @author Wouter
 * @since 06/08/2020
 *
 * @property DungeonRoute $_context
 */
class MapContextDungeonRoute extends MapContext
{
    use DungeonRouteTrait;

    public function __construct(DungeonRoute $dungeonRoute, Floor $floor)
    {
        parent::__construct($dungeonRoute, $floor);
    }

    public function getType(): string
    {
        return 'dungeonroute';
    }

    public function isTeeming(): bool
    {
        return $this->_context->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->_context->seasonal_index;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->_context->dungeon->id, false, $this->_context);
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-route-edit.%s', env('APP_TYPE'), $this->_context->getRouteKey());
    }

    public function getProperties(): array
    {
        return array_merge(parent::getProperties(), $this->getDungeonRouteProperties($this->_context));
    }
}
