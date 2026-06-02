<?php

namespace App\Models\Interfaces;

use App\Models\Floor\Floor;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

/**
 * @property string $vertices_json
 */
interface HasVerticesInterface
{
    /** @return Collection<int, \App\Logic\Structs\LatLng> */
    public function getDecodedLatLngs(?Floor $floor = null): Collection;

    public function getCoordinatesData(CoordinatesServiceInterface $coordinatesService): array;
}
