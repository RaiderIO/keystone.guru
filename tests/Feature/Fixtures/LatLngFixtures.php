<?php

namespace Tests\Feature\Fixtures;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Service\Coordinates\CoordinatesService;

class LatLngFixtures
{
    public static function getLatLng(
        Floor  $floor,
        ?float $lat = null,
        ?float $lng = null,
    ): LatLng {
        return new LatLng(
            $lat ?? random_int(CoordinatesService::MAP_MAX_LAT, 0),
            $lng ?? random_int(0, CoordinatesService::MAP_MAX_LNG),
            $floor,
        );
    }
}
