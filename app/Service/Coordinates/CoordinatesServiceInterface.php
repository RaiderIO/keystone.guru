<?php

namespace App\Service\Coordinates;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Interfaces\ConvertsVerticesInterface;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Model;

interface CoordinatesServiceInterface
{
    public function calculateIngameLocationForMapLocation(LatLng $latLng): IngameXY;

    public function calculateMapLocationForIngameLocation(IngameXY $ingameXY): LatLng;

    public function convertFacadeMapLocationToMapLocation(
        MappingVersion $mappingVersion,
        LatLng         $latLng,
        ?Floor         $forceFloor = null,
    ): LatLng;

    public function convertMapLocationToFacadeMapLocation(
        MappingVersion $mappingVersion,
        LatLng         $latLng,
        ?FloorUnion    $forceFloorUnion = null,
    ): LatLng;

    /**
     * Rewrites the vertices_json of $hasVertices in place with its vertices converted onto the facade. Leaves
     * $hasVertices untouched when $mappingVersion has no facade enabled. A vertex outside every floor union, such as
     * one already on the facade floor, keeps its location and floor.
     *
     * @return Floor The floor the first converted vertex landed on, or $floor when there are no vertices or no facade.
     */
    public function convertVerticesForFacade(
        MappingVersion                  $mappingVersion,
        ConvertsVerticesInterface&Model $hasVertices,
        Floor                           $floor,
    ): Floor;

    public function intersection(LatLng $latLngA1, LatLng $latLngA2, LatLng $latLngB1, LatLng $latLngB2): ?LatLng;

    public function distanceBetweenPoints(float $x1, float $x2, float $y1, float $y2): float;

    public function distance(LatLng $latLngA, LatLng $latLngB): float;

    public function distanceIngameXY(IngameXY $ingameXYA, IngameXY $ingameXYB): float;

    /**
     * @param array<int, array{lat: float, lng: float}> $polygon
     */
    public function polygonContainsPoint(LatLng $latLng, array $polygon): bool;

    public function calculateGridLocationForIngameLocation(
        IngameXY $ingameXY,
        int      $gridSizeX,
        int      $gridSizeY,
    ): IngameXY;
}
