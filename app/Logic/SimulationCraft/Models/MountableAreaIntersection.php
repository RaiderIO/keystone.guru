<?php

namespace App\Logic\SimulationCraft\Models;

use App\Logic\Structs\LatLng;
use App\Models\MountableArea;

class MountableAreaIntersection
{
    public function __construct(private MountableArea $mountableArea, private LatLng $latLng)
    {
    }

    /**
     * @return MountableArea
     */
    public function getMountableArea(): MountableArea
    {
        return $this->mountableArea;
    }

    /**
     * @return LatLng
     */
    public function getLatLng(): LatLng
    {
        return $this->latLng;
    }
}
