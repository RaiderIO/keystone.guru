<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 *
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class UnitDissipates extends SpecialEvent
{
    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);


        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 0;
    }
}
