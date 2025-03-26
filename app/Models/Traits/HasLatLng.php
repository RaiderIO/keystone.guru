<?php

namespace App\Models\Traits;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;

/**
 * @property float|null $lat
 * @property float|null $lng
 * @property Floor|null $floor
 */
trait HasLatLng
{
    public function hasValidLatLng(): bool
    {
        return $this->lat !== null && $this->lng !== null && $this->floor !== null;
    }

    public function getLatLng(): LatLng
    {
        return new LatLng($this->lat, $this->lng, $this->floor);
    }

    public function setLatLng(LatLng $latLng): self
    {
        $this->lat      = $latLng->getLat();
        $this->lng      = $latLng->getLng();
        $this->floor_id = $latLng->getFloor()?->id;

        return $this;
    }

    public function getCoordinatesData(CoordinatesServiceInterface $coordinatesService): array
    {
        if (!$this->hasValidLatLng()) {
            return [];
        }

        $splitFloorsLatLng = $this->getLatLng();

        // If we for some reason currently have facade floor assigned to this map icon (shouldn't happen, but just in case)
        // then we flip the coordinates
        if ($splitFloorsLatLng->getFloor()?->facade) {
            $facadeLatLng      = $splitFloorsLatLng;
            $splitFloorsLatLng = $coordinatesService->convertFacadeMapLocationToMapLocation(
            // Use the direct mapping version, otherwise proxy through dungeonRoute, finally as a fallback use the dungeon's current mapping version
                $this->mappingVersion ?? $this->dungeonRoute->mappingVersion ?? $this->floor->dungeon->currentMappingVersion,
                $facadeLatLng
            );
        } else {
            $facadeLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
            // Use the direct mapping version, otherwise proxy through dungeonRoute, finally as a fallback use the dungeon's current mapping version
                $this->mappingVersion ?? $this->dungeonRoute->mappingVersion ?? $this->floor->dungeon->currentMappingVersion,
                $splitFloorsLatLng
            );
        }

        return [
            'coordinates' => [
                User::MAP_FACADE_STYLE_SPLIT_FLOORS => $splitFloorsLatLng->toArrayWithFloor(),
                User::MAP_FACADE_STYLE_FACADE       => $facadeLatLng->toArrayWithFloor(),
            ],
        ];
    }
}
