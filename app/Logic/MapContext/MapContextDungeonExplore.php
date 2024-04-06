<?php

namespace App\Logic\MapContext;

use App\Models\Dungeon;
use Illuminate\Support\Collection;

/**
 * Class MapContextDungeonExplore
 *
 * @author  Wouter
 *
 * @since   28/08/2023
 *
 * @property Dungeon $context
 */
class MapContextDungeonExplore extends MapContextMappingVersion
{
    public function getFloors(): Collection
    {
        $useFacade = $this->getMapFacadeStyle() === 'facade';

        return $this->floor->dungeon->floorsForMapFacade($this->mappingVersion, $useFacade)->active()->get();
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
