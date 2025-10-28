<?php

namespace App\Logic\MapContext;

use App\Http\Controllers\Traits\ListsEnemies;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;

/**
 * Class MapContextMappingVersionEdit
 *
 * @author  Wouter
 *
 * @since   28/08/2023
 */
class MapContextMappingVersionEdit extends MapContextMappingVersion
{
    use ListsEnemies;

    public function __construct(
        CacheServiceInterface       $cacheService,
        CoordinatesServiceInterface $coordinatesService,
        Dungeon                     $dungeon,
        MappingVersion              $mappingVersion,
    ) {
        parent::__construct($cacheService, $coordinatesService, $dungeon, $mappingVersion, User::MAP_FACADE_STYLE_SPLIT_FLOORS);
    }

    public function getType(): string
    {
        return 'mappingVersionEdit';
    }

    public function getEchoChannelName(): string
    {
        return sprintf('%s-mapping-version-edit.%s', config('app.type'), $this->dungeon->getRouteKey());
    }

    public function getEnemies(): ?array
    {
        try {
            return $this->listEnemies($this->cacheService, $this->coordinatesService, $this->mappingVersion, true) ?? [];
        } catch (InvalidMDTDungeonException) {
            return $this->listEnemies($this->cacheService, $this->coordinatesService, $this->mappingVersion) ?? [];
        }
    }

    public function getVisibleFloors(): array
    {
        return $this->dungeon->floors->toArray();
    }
}
