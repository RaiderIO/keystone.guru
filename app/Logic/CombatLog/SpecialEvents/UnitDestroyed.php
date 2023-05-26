<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 *
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class UnitDestroyed extends SpecialEvent
{
    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {

        return $this;
    }
}
