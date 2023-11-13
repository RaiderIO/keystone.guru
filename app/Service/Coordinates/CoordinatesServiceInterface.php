<?php

namespace App\Service\Coordinates;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;

interface CoordinatesServiceInterface
{
    public function calculateIngameLocationForMapLocation(LatLng $latLng): IngameXY;

    public function calculateMapLocationForIngameLocation(IngameXY $ingameXY): LatLng;

    public function convertFacadeMapLocationToMapLocation(MappingVersion $mappingVersion, LatLng $latLng, ?Floor $forceFloor = null): LatLng;

    public function convertMapLocationToFacadeMapLocation(MappingVersion $mappingVersion, LatLng $latLng): LatLng;
}
