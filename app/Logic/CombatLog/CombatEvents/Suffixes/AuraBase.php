<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

abstract class AuraBase extends Suffix
{
    public const AURA_TYPE_BUFF   = 'BUFF';
    public const AURA_TYPE_DEBUFF = 'DEBUFF';

    /** @var string ex. BUFF */
    private string $auraType;

    private ?int $amount = null;

    public function getAuraType(): string
    {
        return $this->auraType;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * @return HasParameters|$this
     */
    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->auraType = $parameters[0];
        if (isset($parameters[1])) {
            $this->amount = $parameters[1];
        }

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 1;
    }

    public function getParameterCount(): int
    {
        return 2;
    }
}
