<?php

namespace App\Logic\CombatLog;

class CombatLogVersion
{
    public const CLASSIC            = 9;
    public const CLASSIC_SOD_1_15_5 = 9_001_015_005; // Same version - yet there's changes compared to classic, zzzzzzzz
    public const CLASSIC_SOD_1_15_6 = 9_001_015_006;
    public const RETAIL_10_1_0      = 20_010_001_000;
    public const RETAIL_11_0_2      = 21_011_000_002;
    public const RETAIL_11_0_5      = 22_011_000_005;

    public const ALL = [
        self::CLASSIC            => 1,
        self::RETAIL_10_1_0      => 2,
        self::RETAIL_11_0_2      => 3,
        self::RETAIL_11_0_5      => 4,
        self::CLASSIC_SOD_1_15_5 => 5,
        self::CLASSIC_SOD_1_15_6 => 6,
    ];
}
