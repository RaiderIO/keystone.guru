<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Missed;

use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V20\MissedV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V22\MissedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V9SoD\MissedV9SoD;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogVersion;

class MissedBuilder implements SuffixBuilderInterface
{
    public function __construct(
        public int $combatLogVersion
    ) {
    }

    public static function create(int $combatLogVersion): Suffix
    {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC_SOD_1_15_5 => new MissedV9SoD($combatLogVersion),
            CombatLogVersion::CLASSIC, CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2 => new MissedV20($combatLogVersion),
            // This includes
            default => new MissedV22($combatLogVersion),
        };
    }
}
