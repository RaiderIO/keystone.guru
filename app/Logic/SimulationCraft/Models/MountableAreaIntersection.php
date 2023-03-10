<?php

namespace App\Logic\SimulationCraft\Models;

use App\Models\MountableArea;

class MountableAreaIntersection
{
    private MountableArea $mountableArea;

    private float $lat;

    private float $lng;

    /**
     * @param MountableArea $mountableArea
     * @param float $lat
     * @param float $lng
     */
    public function __construct(MountableArea $mountableArea, float $lat, float $lng)
    {
        $this->mountableArea = $mountableArea;
        $this->lat           = $lat;
        $this->lng           = $lng;
    }

    /**
     * @return MountableArea
     */
    public function getMountableArea(): MountableArea
    {
        return $this->mountableArea;
    }

    /**
     * @return float
     */
    public function getLat(): float
    {
        return $this->lat;
    }

    /**
     * @return float
     */
    public function getLng(): float
    {
        return $this->lng;
    }
}
