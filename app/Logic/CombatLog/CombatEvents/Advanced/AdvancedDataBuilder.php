<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced;

use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V20\AdvancedDataV20;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9\AdvancedDataV9;
use App\Logic\CombatLog\CombatLogVersion;

class AdvancedDataBuilder
{
    /**
     * @param int $combatLogVersion
     * @return AdvancedDataInterface
     */
    public static function create(int $combatLogVersion): AdvancedDataInterface
    {
        switch ($combatLogVersion) {
            case CombatLogVersion::CLASSIC:
                return new AdvancedDataV9();
            default:
                return new AdvancedDataV20();
        }
    }
}
