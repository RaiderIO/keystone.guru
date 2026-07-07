<?php

namespace App\Logic\MapContext\Map;

class MapContextDungeonRouteSearch extends MapContextDungeonExplore
{
    #[\Override]
    public function getType(): string
    {
        return 'dungeonRouteSearch';
    }

    #[\Override]
    public function getEchoChannelName(): string
    {
        return sprintf('%s-dungeonroute-search.%s', config('app.type'), $this->dungeon->getRouteKey());
    }
}
