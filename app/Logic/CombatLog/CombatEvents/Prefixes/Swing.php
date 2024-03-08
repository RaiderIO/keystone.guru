<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Swing extends Prefix
{
    public function getParameterCount(): int
    {
        return 0;
    }

    public function setParameters(array $parameters): HasParameters
    {
        return $this;
    }
}
