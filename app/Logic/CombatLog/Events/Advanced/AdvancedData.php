<?php

namespace App\Logic\CombatLog\Events\Advanced;

use App\Logic\CombatLog\Events\Interfaces\HasParameters;

class AdvancedData implements HasParameters
{
    /** @var string[] */
    private array $parameters;

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 17;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {

    }
}
