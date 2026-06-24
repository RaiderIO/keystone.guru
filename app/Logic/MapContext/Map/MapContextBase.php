<?php

namespace App\Logic\MapContext\Map;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
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

    /**
     * @return array<string, mixed>|null
     */
    protected abstract function getEnemies(): ?array;

    /**
     * @return array<string, mixed>
     */
    protected abstract function getVisibleFloors(): array;

    /**
     * @return array<string, mixed>
     */
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
