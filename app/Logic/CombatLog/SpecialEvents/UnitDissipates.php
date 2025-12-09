<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * @author Wouter
 *
 * @since 26/05/2023
 */
class UnitDissipates extends SpecialEvent
{
    #[\Override]
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        return $this;
    }

    public function getParameterCount(): int
    {
        return 0;
    }
}
