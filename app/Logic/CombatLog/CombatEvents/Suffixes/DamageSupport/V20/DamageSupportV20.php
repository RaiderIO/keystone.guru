<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V20;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\DamageSupportInterface;
use App\Logic\CombatLog\Guid\Guid;

class DamageSupportV20 extends DamageV20 implements DamageSupportInterface
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

        $this->supportGuid = Guid::createFromGuidString($parameters[10]);

        return $this;
    }

    #[\Override]
    public function getParameterCount(): int
    {
        return 11;
    }
}
