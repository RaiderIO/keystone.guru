<?php

namespace App\Models\Traits;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;

/**
 * @property float $lat
 * @property float $lng
 *
 * @property Floor $floor
 */
trait HasLatLng
{
    /**
     * @return LatLng
     */
    public function getLatLng(): LatLng
    {
        return new LatLng($this->lat, $this->lng, $this->floor);
    }

    /**
     * @return self
     */
    public function setLatLng(LatLng $latLng): self
    {
        $this->lat      = $latLng->getLat();
        $this->lng      = $latLng->getLng();
        $this->floor_id = optional($latLng->getFloor())->id;

        return $this;
    }
}
