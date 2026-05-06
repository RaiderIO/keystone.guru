<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

class HealSupport extends Heal
{
    private Guid $supportGuid;

    public function getSupportGuid(): ?Guid
    {
        return $this->supportGuid;
    }

    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->supportGuid = Guid::createFromGuidString($parameters[5]);

        return $this;
    }

    #[\Override]
    public function getParameterCount(): int
    {
        return 6;
    }
}
