<?php

namespace App\Service\Coordinates;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;

interface CoordinatesServiceInterface
{
    public function calculateIngameLocationForMapLocation(LatLng $latLng): IngameXY;

    public function calculateMapLocationForIngameLocation(IngameXY $ingameXY): LatLng;
}
