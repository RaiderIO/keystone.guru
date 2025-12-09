<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22_1;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;

class AuraAppliedV22_1 extends AuraBase implements AuraAppliedInterface
{
    private ?int $unknown = null;

    public function getUnknown(): ?int
    {
        return $this->unknown;
    }

    /**
     * @return HasParameters|$this
     */
    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->unknown = $parameters[2] ?? null;

        return $this;
    }

    #[\Override]
    public function getOptionalParameterCount(): int
    {
        return 2;
    }

    #[\Override]
    public function getParameterCount(): int
    {
        return 3;
    }
}
