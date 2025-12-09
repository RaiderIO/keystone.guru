<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * @author Wouter
 *
 * @since 05/06/2023
 */
class WorldMarkerRemoved extends SpecialEvent
{
    private int $marker;

    public function getMarker(): int
    {
        return $this->marker;
    }

    #[\Override]
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->marker = $parameters[0];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 1;
    }
}
