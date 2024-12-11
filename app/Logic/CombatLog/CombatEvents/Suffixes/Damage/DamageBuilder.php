<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Damage;

use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V22\DamageV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogVersion;

class DamageBuilder implements SuffixBuilderInterface
{
    public function __construct(
        public int $combatLogVersion
    ) {
    }

    public static function create(int $combatLogVersion): Suffix
    {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC, CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2 => new DamageV20($combatLogVersion),
            default => new DamageV22($combatLogVersion),
        };
    }
}
