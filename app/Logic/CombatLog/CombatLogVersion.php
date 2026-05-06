<?php

namespace App\Logic\CombatLog;

class CombatLogVersion
{
    public const int CLASSIC            = 9;
    public const int CLASSIC_TBC_2_5_5  = 9_002_005_005;
    public const int CLASSIC_SOD_1_15_5 = 9_001_015_005; // Same version - yet there's changes compared to classic, zzzzzzzz
    public const int CLASSIC_SOD_1_15_6 = 9_001_015_006;
    public const int CLASSIC_SOD_1_15_7 = 9_001_015_007;
    public const int RETAIL_10_1_0      = 20_010_001_000;
    public const int RETAIL_11_0_2      = 21_011_000_002;
    public const int RETAIL_11_0_5      = 22_011_000_005;
    public const int RETAIL_11_0_7      = 22_011_000_007;
    public const int RETAIL_11_1_0      = 22_011_001_000;
    public const int RETAIL_11_1_7      = 22_011_001_007;
    public const int RETAIL_11_2_0      = 22_011_002_000;
    public const int RETAIL_12_0_1      = 22_012_000_001;
    public const int RETAIL_12_0_5      = 22_012_000_005;

    public const array ALL = [
        self::CLASSIC            => 1,
        self::RETAIL_10_1_0      => 2,
        self::RETAIL_11_0_2      => 3,
        self::RETAIL_11_0_5      => 4,
        self::CLASSIC_SOD_1_15_5 => 5,
        self::CLASSIC_SOD_1_15_6 => 6,
        self::RETAIL_11_0_7      => 7,
        self::RETAIL_11_1_0      => 8,
        self::CLASSIC_SOD_1_15_7 => 9,
        self::RETAIL_11_1_7      => 10,
        self::RETAIL_11_2_0      => 11,
        self::RETAIL_12_0_1      => 12,
        self::CLASSIC_TBC_2_5_5  => 13,
        self::RETAIL_12_0_5      => 14,
    ];
}
