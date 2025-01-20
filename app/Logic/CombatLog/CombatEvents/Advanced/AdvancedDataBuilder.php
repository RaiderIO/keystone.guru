<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced;

use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V20\AdvancedDataV20;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V22\AdvancedDataV22;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9\AdvancedDataV9;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9SoD\AdvancedDataV9SoD;
use App\Logic\CombatLog\CombatLogVersion;

class AdvancedDataBuilder
{
    public static function create(int $combatLogVersion): AdvancedDataInterface
    {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC => new AdvancedDataV9(),
            CombatLogVersion::CLASSIC_SOD_1_15_5 => new AdvancedDataV9SoD(),
            CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2 => new AdvancedDataV20(),
            default => new AdvancedDataV22(),
        };
    }
}
