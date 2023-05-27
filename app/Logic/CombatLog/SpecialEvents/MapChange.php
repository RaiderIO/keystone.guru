<?php

namespace App\Logic\CombatLog\SpecialEvents;

class MapChange extends SpecialEvent
{
    private int $uiMapID;

    private string $uiMapName;

    private float $x0;

    private float $x1;

    private float $y0;

    private float $y1;

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
    public function getX0(): float
    {
        return $this->x0;
    }

    /**
     * @return float
     */
    public function getX1(): float
    {
        return $this->x1;
    }

    /**
     * @return float
     */
    public function getY0(): float
    {
        return $this->y0;
    }

    /**
     * @return float
     */
    public function getY1(): float
    {
        return $this->y1;
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
        $this->x0        = $parameters[2];
        $this->x1        = $parameters[3];
        $this->y0        = $parameters[4];
        $this->y1        = $parameters[5];

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
