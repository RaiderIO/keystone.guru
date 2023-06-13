<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

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
