<?php

namespace App\Logic\CombatLog\SpecialEvents;

class CombatantInfo extends SpecialEvent
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
