<?php

namespace App\Logic\CombatLog;

class CombatLogVersion
{
    public const CLASSIC = 9;

    public const RETAIL_10_1_0 = 20;

    public const RETAIL_11_0_2 = 21;

    public const RETAIL_11_0_5 = 22;

    public const ALL = [
        self::CLASSIC       => 1,
        self::RETAIL_10_1_0 => 2,
        self::RETAIL_11_0_2 => 3,
        self::RETAIL_11_0_5 => 4,
    ];
}
