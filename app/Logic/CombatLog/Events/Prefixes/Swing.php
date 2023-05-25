<?php

namespace App\Logic\CombatLog\Events\Prefixes;

use App\Logic\CombatLog\Events\Interfaces\HasParameters;

class Swing extends Prefix
{

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 0;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        return $this;
    }
}
