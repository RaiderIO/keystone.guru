<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class CastFailed extends Suffix
{

    /** @var string ex. Not yet recovered */
    private string $failedType;

    /**
     * @return string
     */
    public function getFailedType(): string
    {
        return $this->failedType;
    }

    /**
     * @param array $parameters
     * @return HasParameters|$this
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->failedType = $parameters[0];

        return $this;
    }


    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 1;
    }
}
