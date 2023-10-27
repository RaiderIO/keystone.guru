<?php

namespace App\Logic\SimulationCraft\Models;

use App\Logic\Structs\LatLng;
use App\Models\MountableArea;

class MountableAreaIntersection
{
    private MountableArea $mountableArea;

    private LatLng $latLng;

    /**
     * @param MountableArea $mountableArea
     * @param LatLng        $latLng
     */
    public function __construct(MountableArea $mountableArea, LatLng $latLng)
    {
        $this->mountableArea = $mountableArea;
        $this->latLng        = $latLng;
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
