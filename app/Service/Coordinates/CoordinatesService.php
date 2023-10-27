<?php

namespace App\Service\Coordinates;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;

class CoordinatesService implements CoordinatesServiceInterface
{

    /** @var int Y */
    const MAP_MAX_LAT = -256;

    /** @var int X */
    const MAP_MAX_LNG = 384;

    /**
     * @param LatLng $latLng
     * @return IngameXY
     */
    public function calculateIngameLocationForMapLocation(LatLng $latLng): IngameXY
    {
        $targetFloor = $latLng->getFloor();

        if ($targetFloor === null) {
            throw new \InvalidArgumentException('No floor set for latlng!');
        }

        // Check if this floor has unions.
        // If it has unions, check if the lat/lng is inside the union floor area
        // If it is, we must use the target floor of the union instead to fetch the ingame_max_x etc.
        // Then, we must apply rotation to the MAP location (rotate it around union lat/lng) and do the conversion
        foreach ($targetFloor->floorUnions as $floorUnion) {
            foreach ($floorUnion->floorUnionAreas as $floorUnionArea) {
                if ($floorUnionArea->containsPoint($latLng)) {
                    // Ok this lat lng is inside a floor union area - this means we must use it's attached floor union's target floor
                    $targetFloor = $floorUnion->targetFloor;

                    // @TODO rotate latLng around floor union point
                    $latLng->rotate($floorUnion->getLatLng(), $floorUnion->rotation);
                }
            }
        }

        $ingameMapSizeX = $targetFloor->ingame_max_x - $targetFloor->ingame_min_x;
        $ingameMapSizeY = $targetFloor->ingame_max_y - $targetFloor->ingame_min_y;

        // Invert the lat/lngs
        $factorLat = ((self::MAP_MAX_LAT - $latLng->getLat()) / self::MAP_MAX_LAT);
        $factorLng = ((self::MAP_MAX_LNG - $latLng->getLng()) / self::MAP_MAX_LNG);

        return new IngameXY(
            ($ingameMapSizeX * $factorLng) + $targetFloor->ingame_min_x,
            ($ingameMapSizeY * $factorLat) + $targetFloor->ingame_min_y,
            $targetFloor
        );
    }

    /**
     * @param IngameXY $ingameXY
     * @return LatLng
     */
    public function calculateMapLocationForIngameLocation(IngameXY $ingameXY): LatLng
    {
        $targetFloor = $ingameXY->getFloor();

        if ($targetFloor === null) {
            throw new InvalidArgumentException('No floor set for ingame XY!');
        }

        $ingameMapSizeX = $targetFloor->ingame_max_x - $targetFloor->ingame_min_x;
        $ingameMapSizeY = $targetFloor->ingame_max_y - $targetFloor->ingame_min_y;

        $factorX = (($targetFloor->ingame_min_x - $ingameXY->getX()) / $ingameMapSizeX);
        $factorY = (($targetFloor->ingame_min_y - $ingameXY->getY()) / $ingameMapSizeY);

        return new LatLng(
            (self::MAP_MAX_LAT * $factorY) + self::MAP_MAX_LAT,
            (self::MAP_MAX_LNG * $factorX) + self::MAP_MAX_LNG,
            $targetFloor
        );
    }
}
