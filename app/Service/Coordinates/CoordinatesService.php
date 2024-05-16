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
    public const MAP_MAX_LAT = -256;

    /** @var int X */
    public const MAP_MAX_LNG = 384;

    /** @var int */
    public const MAP_SIZE = 256;

    /** @var int */
    public const MAP_ASPECT_RATIO = 1.5;

    /**
     * @see mapcontext.js
     */
    public function calculateIngameLocationForMapLocation(LatLng $latLng): IngameXY
    {
        $floor = $latLng->getFloor();

        if ($floor === null) {
            throw new InvalidArgumentException('No floor set for latlng!');
        } else if ($floor->facade) {
            throw new InvalidArgumentException(
                sprintf('Unable to convert latlng %s that is on facade floor!', json_encode($latLng->toArrayWithFloor()))
            );
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

    public function calculateMapLocationForIngameLocation(IngameXY $ingameXY): LatLng
    {
        $targetFloor = $ingameXY->getFloor();

        if ($targetFloor === null) {
            throw new InvalidArgumentException('No floor set for ingame XY!');
        } else if ($targetFloor->facade) {
            sprintf('Unable to convert ingame XY %s that is on facade floor!', json_encode($ingameXY->toArrayWithFloor()));
        }

        $ingameMapSizeX = $targetFloor->ingame_max_x - $targetFloor->ingame_min_x;
        $ingameMapSizeY = $targetFloor->ingame_max_y - $targetFloor->ingame_min_y;

        if ((int)$ingameMapSizeX === 0 || (int)$ingameMapSizeY === 0) {
            throw new InvalidArgumentException(
                sprintf('Floor %s (%d) does not have ingame coordinates set!', __($targetFloor->name, [], 'en_US'), $targetFloor->id)
            );
        }

        $factorX = (($targetFloor->ingame_min_x - $ingameXY->getX()) / $ingameMapSizeX);
        $factorY = (($targetFloor->ingame_min_y - $ingameXY->getY()) / $ingameMapSizeY);

        return new LatLng(
            (self::MAP_MAX_LAT * $factorY) + self::MAP_MAX_LAT,
            (self::MAP_MAX_LNG * $factorX) + self::MAP_MAX_LNG,
            $targetFloor
        );
    }

    public function convertFacadeMapLocationToMapLocation(MappingVersion $mappingVersion, LatLng $latLng, ?Floor $forceFloor = null): LatLng
    {
        $sourceFloor = $latLng->getFloor();
        if ($sourceFloor === null) {
            throw new InvalidArgumentException('No floor set for latlng!');
        }

        $result = clone $latLng;
        // Nothing to do if facade is not enabled - the coordinates are the same always
        if (!$mappingVersion->facade_enabled) {
            return $result;
        }

        // Check if this floor has unions.
        // If it has unions, check if the lat/lng is inside the union floor area
        // If it is, we must use the target floor of the union instead to fetch the ingame_max_x etc.
        // Then, we must apply rotation to the MAP location (rotate it around union lat/lng) and do the conversion
        $floorUnions = $mappingVersion->getFloorUnionsOnFloor($sourceFloor->id);

        foreach ($floorUnions as $floorUnion) {
            /** @var FloorUnion $floorUnion */

            // We must find the floor union we should perform our translation on
            $targetFloor = null;

            // If we're forcing the translation on a certain floor, check if this floor union matches that forced floor
            if ($floorUnion->target_floor_id === $forceFloor?->id) {
                $targetFloor = $forceFloor;
            } else {
                // Otherwise, check if the floor union area contains the target point, then we use this floor union's
                // target floor
                foreach ($floorUnion->floorUnionAreas as $floorUnionArea) {
                    if ($floorUnionArea->containsPoint($this, $latLng)) {
                        $targetFloor = $floorUnion->targetFloor;
                        break;
                    }
                }
            }

            // Did we find the target floor, either through forced floor or through the floor union area?
            if ($targetFloor !== null) {
                $result->setFloor($targetFloor);

                // 1. Rotate the point according to the floor union's rotation
                $result->rotate($floorUnion->getLatLng(), $floorUnion->rotation);

                // Move the point according to the floor union's latlng + size
                // 2. Scale the point from the current floor map to the new floor map
                $result->scale(
                    $floorUnion->getLatLng(),
                    $floorUnion->size,
                    self::getMapCenterLatLng($floorUnion->targetFloor),
                    self::MAP_SIZE
                );

                // The point is now on the new map plane
                break;
            }

        }

        return $result;
    }

    public function convertMapLocationToFacadeMapLocation(MappingVersion $mappingVersion, LatLng $latLng, ?FloorUnion $forceFloorUnion = null): LatLng
    {
        $sourceFloor = $latLng->getFloor();

        if ($sourceFloor === null) {
            throw new InvalidArgumentException('No floor set for latlng!');
        }

        // Check if this floor has unions.
        // If it has unions, check if the lat/lng is inside the union floor area
        // If it is, we must use the target floor of the union instead to fetch the ingame_max_x etc.
        // Then, we must apply rotation to the MAP location (rotate it around union lat/lng) and do the conversion
        /** @var FloorUnion $floorUnion */
        $floorUnion = $forceFloorUnion ?? $mappingVersion->getFloorUnionForLatLng($this, $mappingVersion, $latLng);

        // No floor unions mean we don't need to do anything - we're done
        if ($floorUnion === null) {
            return $latLng;
        }

        $result = clone $latLng;

        // Ok this lat lng is inside a floor union area - this means we must use it's attached floor union's target floor
        $result->setFloor($floorUnion->floor);

        // Move the enemy according to the floor union's latlng + size
        // 1. Scale the point from the current floor map to the new floor map
        $result->scale(
            self::getMapCenterLatLng($floorUnion->targetFloor),
            self::MAP_SIZE,
            $floorUnion->getLatLng(),
            $floorUnion->size
        );

        // 2. Rotate the point according to the floor union's rotation
        $result->rotate($floorUnion->getLatLng(), $floorUnion->rotation * -1);

        return $result;
    }

    public function distanceBetweenPoints(float $x1, float $x2, float $y1, float $y2): float
    {
        // Pythagoras theorem: a^2+b^2=c^2
        return sqrt(
            ($x1 - $x2) ** 2 +
            ($y1 - $y2) ** 2
        );
    }

    /**
     * @return array{lng: float, lat: float}|null
     */
    public function intersection(LatLng $latLngA1, LatLng $latLngA2, LatLng $latLngB1, LatLng $latLngB2): ?LatLng
    {
        // Line AB represented as a1lng + b1lat = c1
        $a1 = $latLngA2->getLat() - $latLngA1->getLat();
        $b1 = $latLngA1->getLng() - $latLngA2->getLng();
        $c1 = $a1 * ($latLngA1->getLng()) + $b1 * ($latLngA1->getLat());

        // Line CD represented as a2lng + b2lat = c2
        $a2 = $latLngB2->getLat() - $latLngB1->getLat();
        $b2 = $latLngB1->getLng() - $latLngB2->getLng();
        $c2 = $a2 * ($latLngB1->getLng()) + $b2 * ($latLngB1->getLat());

        $determinant = $a1 * $b2 - $a2 * $b1;

        if ($determinant == 0) {
            // The lines are parallel and will never intersect
            return null;
        } else {
            $lng = ($b2 * $c1 - $b1 * $c2) / $determinant;
            $lat = ($a1 * $c2 - $a2 * $c1) / $determinant;

            $l1Length = $this->distanceBetweenPoints($latLngA1->getLng(), $latLngA2->getLng(), $latLngA1->getLat(), $latLngA2->getLat());
            // If the distance to the found point is greater than the length of EITHER of the lines, it's not a correct intersection!
            // This means that the intersection occurred in the extended line past the points of $p1 and $p2. We don't want them.
            if ($l1Length < $this->distanceBetweenPoints($latLngA1->getLng(), $lng, $latLngA1->getLat(), $lat) ||
                $l1Length < $this->distanceBetweenPoints($latLngA2->getLng(), $lng, $latLngA2->getLat(), $lat)
            ) {
                return null;
            }

            $l2Length = $this->distanceBetweenPoints($latLngB1->getLng(), $latLngB2->getLng(), $latLngB1->getLat(), $latLngB2->getLat());
            if ($l2Length < $this->distanceBetweenPoints($latLngB1->getLng(), $lng, $latLngB1->getLat(), $lat) ||
                $l2Length < $this->distanceBetweenPoints($latLngB2->getLng(), $lng, $latLngB2->getLat(), $lat)
            ) {
                return null;
            }

            return LatLng::fromArray(['lat' => $lat, 'lng' => $lng]);
        }
    }

    public function polygonContainsPoint(LatLng $latLng, array $polygon): bool
    {
        if ($polygon[0] != $polygon[count($polygon) - 1]) {
            $polygon[] = $polygon[0];
        }

        $j        = 0;
        $oddNodes = false;
        $lat      = $latLng->getLat();
        $lng      = $latLng->getLng();
        $n        = count($polygon);
        for ($i = 0; $i < $n; $i++) {
            $j++;
            if ($j == $n) {
                $j = 0;
            }

            if ((($polygon[$i]['lng'] < $lng) && ($polygon[$j]['lng'] >= $lng)) || (($polygon[$j]['lng'] < $lng) && ($polygon[$i]['lng'] >=
                        $lng))) {
                if ($polygon[$i]['lat'] + ($lng - $polygon[$i]['lng']) / ($polygon[$j]['lng'] - $polygon[$i]['lng']) * ($polygon[$j]['lat'] -
                        $polygon[$i]['lat']) < $lat) {
                    $oddNodes = !$oddNodes;
                }
            }
        }

        return $oddNodes;
    }

    public static function getMapCenterLatLng(?Floor $floor = null): LatLng
    {
        return new LatLng(
            self::MAP_MAX_LAT / 2,
            self::MAP_MAX_LNG / 2,
            $floor
        );
    }
}
