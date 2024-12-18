<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport;

use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\V20\DamageLandedSupportV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\V22\DamageLandedSupportV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogVersion;

class DamageLandedSupportBuilder implements SuffixBuilderInterface
{
    public function __construct(
        public int $combatLogVersion
    ) {
    }

    public static function create(int $combatLogVersion): Suffix
    {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC, CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2 => new DamageLandedSupportV20($combatLogVersion),
            default => new DamageLandedSupportV22($combatLogVersion),
        };
    }
}
