<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 *
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 05/06/2023
 */
class WorldMarkerRemoved extends SpecialEvent
{
    private int $marker;

    /**
     * @return int
     */
    public function getMarker(): int
    {
        return $this->marker;
    }
    
    /**
     * @param array $parameters
     *
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->marker = $parameters[0];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 1;
    }
}
