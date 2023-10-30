<?php

namespace App\Service\Coordinates;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use InvalidArgumentException;

class CoordinatesService implements CoordinatesServiceInterface
{

    /** @var int Y */
    const MAP_MAX_LAT = -256;

    /** @var int X */
    const MAP_MAX_LNG = 384;

    /** @var int */
    const MAP_SIZE = 256;

    /** @var int */
    const MAP_ASPECT_RATIO = 1.5;

    /**
     * @param LatLng $latLng
     * @return IngameXY
     */
    public function calculateIngameLocationForMapLocation(LatLng $latLng): IngameXY
    {
        $floor = $latLng->getFloor();

        if ($floor === null) {
            throw new InvalidArgumentException('No floor set for latlng!');
        }

        $ingameMapSizeX = $floor->ingame_max_x - $floor->ingame_min_x;
        $ingameMapSizeY = $floor->ingame_max_y - $floor->ingame_min_y;

        // Invert the lat/lngs
        $factorLat = ((self::MAP_MAX_LAT - $latLng->getLat()) / self::MAP_MAX_LAT);
        $factorLng = ((self::MAP_MAX_LNG - $latLng->getLng()) / self::MAP_MAX_LNG);

        return new IngameXY(
            ($ingameMapSizeX * $factorLng) + $floor->ingame_min_x,
            ($ingameMapSizeY * $factorLat) + $floor->ingame_min_y,
            $floor
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

    /**
     * @param MappingVersion $mappingVersion
     * @param LatLng         $latLng
     * @return LatLng
     */
    public function convertFacadeMapLocationToMapLocation(MappingVersion $mappingVersion, LatLng $latLng): LatLng
    {
        $targetFloor = $latLng->getFloor();

        if ($targetFloor === null) {
            throw new \InvalidArgumentException('No floor set for latlng!');
        }

        $result = clone $latLng;

        // Check if this floor has unions.
        // If it has unions, check if the lat/lng is inside the union floor area
        // If it is, we must use the target floor of the union instead to fetch the ingame_max_x etc.
        // Then, we must apply rotation to the MAP location (rotate it around union lat/lng) and do the conversion
        $floorUnions = $mappingVersion
            ->floorUnions()
            ->where('floor_id', $targetFloor->id)
            ->with(['floor', 'targetFloor'])
            ->get();

        foreach ($floorUnions as $floorUnion) {
            /** @var FloorUnion $floorUnion */
            foreach ($floorUnion->floorUnionAreas as $floorUnionArea) {
                if ($floorUnionArea->containsPoint($latLng)) {
                    // Ok this lat lng is inside a floor union area - this means we must use it's attached floor union's target floor
                    $result->setFloor($floorUnion->targetFloor);

                    // Move the enemy according to the floor union's latlng + size
                    // 1. Scale the point from the current floor map to the new floor map
                    $result->scale(
                        $floorUnion->getLatLng(),
                        $floorUnion->size,
                        self::getMapCenterLatLng($floorUnion->targetFloor),
                        self::MAP_SIZE
                    );

                    // 2. Rotate the point according to the floor union's rotation
                    $result->rotate($floorUnion->getLatLng(), $floorUnion->rotation);

                    // The point is now on the new map plane
                }
            }
        }

        return $result;
    }

    /**
     * @param Floor|null $floor
     * @return LatLng
     */
    public static function getMapCenterLatLng(?Floor $floor = null): LatLng
    {
        return new LatLng(
            self::MAP_MAX_LAT / 2,
            self::MAP_MAX_LNG / 2,
            $floor
        );
    }
}
