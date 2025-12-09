<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V22;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;

class DamageV22 extends DamageV20
{
    /**
     * @var string|null "AOE" or "ST"
     */
    private ?string $damageType = null;

    public function getDamageType(): ?string
    {
        return $this->damageType;
    }

    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->damageType = $parameters[10] ?? null;

        return $this;
    }

    public function getParameterCount(): int
    {
        return 11;
    }

    public function getOptionalParameterCount(): int
    {
        return 1;
    }
}
