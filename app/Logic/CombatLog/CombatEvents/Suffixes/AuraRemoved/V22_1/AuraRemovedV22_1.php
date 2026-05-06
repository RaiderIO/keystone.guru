<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\V22_1;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\AuraRemovedInterface;

class AuraRemovedV22_1 extends AuraBase implements AuraRemovedInterface
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
