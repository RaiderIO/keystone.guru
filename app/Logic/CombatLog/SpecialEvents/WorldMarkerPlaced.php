<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 *
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 05/06/2023
 */
class WorldMarkerPlaced extends SpecialEvent
{
    private int $instanceId;

    private int $marker;

    private float $positionX;

    private float $positionY;

    /**
     * @return int
     */
    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    /**
     * @return int
     */
    public function getMarker(): int
    {
        return $this->marker;
    }

    /**
     * @return float
     */
    public function getPositionX(): float
    {
        return $this->positionX;
    }

    /**
     * @return float
     */
    public function getPositionY(): float
    {
        return $this->positionY;
    }

    /**
     * @param array $parameters
     *
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->instanceId = $parameters[0];
        $this->marker     = $parameters[1];
        $this->positionX  = $parameters[2];
        $this->positionY  = $parameters[3];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 4;
    }
}
