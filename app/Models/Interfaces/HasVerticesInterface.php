<?php

namespace App\Models\Interfaces;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

/**
 * @property string $vertices_json
 */
interface HasVerticesInterface
{
    /** @return Collection<int, LatLng> */
    public function getDecodedLatLngs(?Floor $floor = null): Collection;

    /** @return array<string, mixed> */
    public function getCoordinatesData(
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        Floor                       $floor,
    ): array;
}
