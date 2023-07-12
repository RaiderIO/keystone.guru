<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

class HealSupport extends Heal
{
    private Guid $supportGuid;

    /**
     * @return Guid|null
     */
    public function getSupportGuid(): ?Guid
    {
        return $this->supportGuid;
    }

    /**
     * @param array $parameters
     * @return HasParameters
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->supportGuid = Guid::createFromGuidString($parameters[5]);

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
