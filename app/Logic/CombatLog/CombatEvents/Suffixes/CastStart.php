<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

class CastStart extends Suffix
{

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 0;
    }
}
