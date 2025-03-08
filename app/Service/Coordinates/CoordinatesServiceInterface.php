<?php

namespace App\Service\Coordinates;

use App\Logic\Structs\GridLocation;
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

    public function intersection(LatLng $latLngA1, LatLng $latLngA2, LatLng $latLngB1, LatLng $latLngB2): ?LatLng;

    public function distance(LatLng $latLngA, LatLng $latLngB): float;

    public function distanceIngameXY(IngameXY $ingameXYA, IngameXY $ingameXYB): float;

    public function polygonContainsPoint(LatLng $latLng, array $polygon): bool;

    public function calculateGridLocationForIngameLocation(IngameXY $ingameXY, int $gridSizeX, int $gridSizeY): IngameXY;
}
