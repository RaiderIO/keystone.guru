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
 * @property DungeonRoute $context
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
        return $this->context->teeming;
    }

    public function getSeasonalIndex(): int
    {
        return $this->context->seasonal_index;
    }

    public function getEnemies(): array
    {
        return $this->listEnemies($this->context->dungeon->id, false);
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-route-edit.%s', config('app.type'), $this->context->getRouteKey());
    }

    public function getProperties(): array
    {
        return array_merge(parent::getProperties(), $this->getDungeonRouteProperties($this->context));
    }
}
