<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Energize;

use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V22\EnergizeV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V9\EnergizeV9;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogVersion;

class EnergizeBuilder implements SuffixBuilderInterface
{
    public function __construct(
        public int $combatLogVersion,
    ) {
    }

    public static function create(int $combatLogVersion): Suffix
    {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC_SOD_1_15_7,
            CombatLogVersion::CLASSIC_TBC_2_5_5 => new EnergizeV9($combatLogVersion),
            default                             => new EnergizeV22($combatLogVersion),
        };
    }
}
