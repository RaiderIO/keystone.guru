<?php


namespace App\Logic\MapContext;

use App\Models\Dungeon;

/**
 * Class MapContextDungeonExplore
 * @package App\Logic\MapContext
 * @author  Wouter
 * @since   28/08/2023
 *
 * @property Dungeon $context
 */
class MapContextDungeonExplore extends MapContextMappingVersion
{
    public function getMapFacadeStyle(): string
    {
        return $_COOKIE['map_facade_style'] ?? 'facade';
    }

    public function getType(): string
    {
        return 'dungeonExplore';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-dungeon-explore.%s', config('app.type'), $this->context->getRouteKey());
    }
}
