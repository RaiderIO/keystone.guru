<?php

namespace App\Logic\MapContext\Map;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;

/**
 * Class MapContextDungeonExplore
 *
 * @author  Wouter
 *
 * @since   28/08/2023
 */
class MapContextDungeonExplore extends MapContextMappingVersion
{
    use ListsEnemies;

    public function __construct(
        CacheServiceInterface                   $cacheService,
        CoordinatesServiceInterface             $coordinatesService,
        private readonly SeasonServiceInterface $seasonService,
        Dungeon                                 $dungeon,
        MappingVersion                          $mappingVersion,
        string                                  $mapFacadeStyle,
    ) {
        parent::__construct($cacheService, $coordinatesService, $dungeon, $mappingVersion, $mapFacadeStyle);
    }

    public function getVisibleFloors(): array
    {
        return $this->dungeon->floorsForMapFacade(
            $this->mappingVersion,
            $this->mapFacadeStyle === User::MAP_FACADE_STYLE_FACADE,
        )->active()->get()->toArray();
    }

    public function getType(): string
    {
        return 'dungeonExplore';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-dungeon-explore.%s', config('app.type'), $this->dungeon->getRouteKey());
    }

    public function getEnemies(): ?array
    {
        // Do not override the enemies
        return null;
    }

    #[\Override]
    public function toArray(): array
    {
        $activeSeason = $this->dungeon->getActiveSeason($this->seasonService);

        return array_merge(parent::toArray(), [
            'featuredAffixes'   => $activeSeason?->getFeaturedAffixes() ?? [],
            'seasonStartPeriod' => $activeSeason?->start_period ?? 0,
        ]);
    }
}
