<?php

namespace App\Logic\MapContext;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use DragonCode\Contracts\Support\Arrayable;

abstract class MapContextBase implements Arrayable
{
    use ListsEnemies;

    public function __construct(
        protected readonly CacheServiceInterface       $cacheService,
        protected readonly CoordinatesServiceInterface $coordinatesService,
        protected readonly Dungeon                     $dungeon,
        protected readonly MappingVersion              $mappingVersion,
        protected readonly string                      $mapFacadeStyle,
    ) {
    }

    protected abstract function getType(): string;

    protected abstract function getEchoChannelName(): string;

    protected abstract function getEnemies(): ?array;

    protected abstract function getVisibleFloors(): array;

    public function toArray(): array
    {
        // Enemies may be null - filter them out
        return array_filter([
            'type'            => $this->getType(),
            'enemies'         => $this->getEnemies(),
            'echoChannelName' => $this->getEchoChannelName(),
            'visibleFloors'   => $this->getVisibleFloors(),
        ]);
    }
}
