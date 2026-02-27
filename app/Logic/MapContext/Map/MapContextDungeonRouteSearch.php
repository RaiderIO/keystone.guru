<?php

namespace App\Logic\MapContext\Map;

class MapContextDungeonRouteSearch extends MapContextDungeonExplore
{
    public function getType(): string
    {
        return 'dungeonRouteSearch';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-dungeonroute-search.%s', config('app.type'), $this->dungeon->getRouteKey());
    }
}
