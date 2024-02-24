<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced;

use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V20\AdvancedDataV20;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9\AdvancedDataV9;
use App\Logic\CombatLog\CombatLogVersion;

class AdvancedDataBuilder
{
    /**
     * @return AdvancedDataInterface
     */
    public static function create(int $combatLogVersion): AdvancedDataInterface
    {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC => new AdvancedDataV9(),
            default => new AdvancedDataV20(),
        };
    }
}
