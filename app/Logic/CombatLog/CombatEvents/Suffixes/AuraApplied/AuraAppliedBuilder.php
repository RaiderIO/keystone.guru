<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;

use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22\AuraAppliedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22_1\AuraAppliedV22_1;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogVersion;

class AuraAppliedBuilder implements SuffixBuilderInterface
{
    public function __construct(
        public int $combatLogVersion
    ) {
    }

    public static function create(int $combatLogVersion): Suffix
    {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC,
            CombatLogVersion::CLASSIC_SOD_1_15_5,
            CombatLogVersion::CLASSIC_SOD_1_15_6,
            CombatLogVersion::CLASSIC_SOD_1_15_7,
            CombatLogVersion::RETAIL_10_1_0,
            CombatLogVersion::RETAIL_11_0_2,
            CombatLogVersion::RETAIL_11_0_5,
            CombatLogVersion::RETAIL_11_0_7,
            CombatLogVersion::RETAIL_11_1_0 => new AuraAppliedV22($combatLogVersion),
            default => new AuraAppliedV22_1($combatLogVersion),
        };
    }
}
