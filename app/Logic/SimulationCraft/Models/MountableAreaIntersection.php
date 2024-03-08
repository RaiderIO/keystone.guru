<?php

namespace App\Logic\SimulationCraft\Models;

use App\Logic\Structs\LatLng;
use App\Models\MountableArea;

class MountableAreaIntersection
{
    public function __construct(private readonly MountableArea $mountableArea, private readonly LatLng $latLng)
    {
    }

    public function getMountableArea(): MountableArea
    {
        return $this->mountableArea;
    }

    public function getLatLng(): LatLng
    {
        return $this->latLng;
    }
}
