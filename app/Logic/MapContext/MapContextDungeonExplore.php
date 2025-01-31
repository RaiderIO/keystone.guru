<?php

namespace App\Logic\MapContext;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
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
    public function __construct(
        CacheServiceInterface            $cacheService,
        CoordinatesServiceInterface      $coordinatesService,
        protected SeasonServiceInterface $seasonService,
        Dungeon                          $dungeon,
        Floor                            $floor,
        MappingVersion                   $mappingVersion)
    {
        parent::__construct($cacheService, $coordinatesService, $dungeon, $floor, $mappingVersion);
    }


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

    public function getProperties(): array
    {
        $activeSeason = $this->context->getActiveSeason($this->seasonService);

        return array_merge([
            'featuredAffixes'   => $activeSeason?->getFeaturedAffixes() ?? [],
            'seasonStartPeriod' => $activeSeason?->start_period ?? 0,
        ], parent::getProperties());
    }
}
