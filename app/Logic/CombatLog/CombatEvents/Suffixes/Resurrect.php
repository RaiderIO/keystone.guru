<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

class Resurrect extends Suffix
{
    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 0;
    }

}
