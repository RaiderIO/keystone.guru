<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V22;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V22\DamageV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\DamageSupportInterface;
use App\Logic\CombatLog\Guid\Guid;

class DamageSupportV22 extends DamageV22 implements DamageSupportInterface
{

    private Guid $supportGuid;

    public function getSupportGuid(): ?Guid
    {
        return $this->supportGuid;
    }

    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->supportGuid = Guid::createFromGuidString($parameters[11]);

        return $this;
    }

    public function getParameterCount(): int
    {
        return 12;
    }
}
