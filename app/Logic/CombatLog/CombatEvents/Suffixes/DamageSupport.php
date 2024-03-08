<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

class DamageSupport extends Damage
{
    private Guid $supportGuid;

    public function getSupportGuid(): ?Guid
    {
        return $this->supportGuid;
    }

    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->supportGuid = Guid::createFromGuidString($parameters[10]);

        return $this;
    }

    public function getParameterCount(): int
    {
        return 11;
    }
}
