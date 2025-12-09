<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class ExtraAttacks extends Suffix
{
    private int $amount;

    public function getAmount(): int
    {
        return $this->amount;
    }

    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->amount = $parameters[0];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 1;
    }
}
