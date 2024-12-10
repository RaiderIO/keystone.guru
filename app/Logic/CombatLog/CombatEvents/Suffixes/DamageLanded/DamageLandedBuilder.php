<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded;

use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\V20\DamageLandedV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\V22\DamageLandedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogVersion;

class DamageLandedBuilder implements SuffixBuilderInterface
{
    public static function create(int $combatLogVersion): Suffix
    {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC, CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2 => new DamageLandedV20($combatLogVersion),
            default => new DamageLandedV22($combatLogVersion),
        };
    }
}
