<?php

namespace App\Models\Traits;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;

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
}
