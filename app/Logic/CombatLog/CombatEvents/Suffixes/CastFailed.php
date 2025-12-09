<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class CastFailed extends Suffix
{
    /** @var string ex. Not yet recovered */
    private string $failedType;

    public function getFailedType(): string
    {
        return $this->failedType;
    }

    /**
     * @return HasParameters|$this
     */
    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->failedType = $parameters[0];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 1;
    }
}
