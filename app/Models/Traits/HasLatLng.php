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

    protected function getCoordinatesData(CoordinatesServiceInterface $coordinatesService): array
    {
        $splitFloorsLatLng = $this->getLatLng();

        // If we for some reason currently have facade floor assigned to this map icon (shouldn't happen, but just in case)
        // then we flip the coordinates
        if ($splitFloorsLatLng->getFloor()?->facade) {
            $facadeLatLng      = $splitFloorsLatLng;
            $splitFloorsLatLng = $coordinatesService->convertFacadeMapLocationToMapLocation(
                $this->mappingVersion ?? $this->dungeonRoute->mappingVersion,
                $facadeLatLng
            );
        } else {
            $facadeLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                $this->mappingVersion ?? $this->dungeonRoute->mappingVersion,
                $splitFloorsLatLng
            );
        }

        return [
            'coordinates' => [
                User::MAP_FACADE_STYLE_SPLIT_FLOORS => $splitFloorsLatLng->toArray(),
                User::MAP_FACADE_STYLE_FACADE       => $facadeLatLng->toArray(),
            ],
        ];
    }
}
