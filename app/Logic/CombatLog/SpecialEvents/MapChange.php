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

    public function getUiMapID(): int
    {
        return $this->uiMapID;
    }

    public function getUiMapName(): string
    {
        return $this->uiMapName;
    }

    public function getXMax(): float
    {
        return $this->xMax;
    }

    public function getXMin(): float
    {
        return $this->xMin;
    }

    public function getYMax(): float
    {
        return $this->yMax;
    }

    public function getYMin(): float
    {
        return $this->yMin;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->uiMapID = $parameters[0];
        $this->uiMapName = $parameters[1];
        $this->xMax = $parameters[4] * -1;
        $this->xMin = $parameters[5] * -1;
        $this->yMax = $parameters[2];
        $this->yMin = $parameters[3];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 6;
    }
}
