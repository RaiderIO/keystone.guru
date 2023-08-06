<?php

namespace App\Logic\CombatLog\SpecialEvents;

class MapChange extends SpecialEvent
{
    private int $uiMapID;

    private string $uiMapName;

    private float $xMax;

    private float $xMin;

    private float $yMax;

    private float $yMin;

    /**
     * @return int
     */
    public function getUiMapID(): int
    {
        return $this->uiMapID;
    }

    /**
     * @return string
     */
    public function getUiMapName(): string
    {
        return $this->uiMapName;
    }

    /**
     * @return float
     */
    public function getXMax(): float
    {
        return $this->xMax;
    }

    /**
     * @return float
     */
    public function getXMin(): float
    {
        return $this->xMin;
    }

    /**
     * @return float
     */
    public function getYMax(): float
    {
        return $this->yMax;
    }

    /**
     * @return float
     */
    public function getYMin(): float
    {
        return $this->yMin;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->uiMapID   = $parameters[0];
        $this->uiMapName = $parameters[1];
        $this->xMax      = $parameters[2];
        $this->xMin      = $parameters[3];
        $this->yMax      = $parameters[4];
        $this->yMin      = $parameters[5];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 6;
    }


}
