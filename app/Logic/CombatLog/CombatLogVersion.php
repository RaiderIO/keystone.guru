<?php

namespace App\Logic\CombatLog;

class CombatLogVersion
{
    public const CLASSIC = 9;

    public const RETAIL = 20;

    public const ALL = [
        self::CLASSIC => 1,
        self::RETAIL => 2,
    ];
}
