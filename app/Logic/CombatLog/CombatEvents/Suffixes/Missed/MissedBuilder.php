<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Missed;

use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V20\MissedV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V22\MissedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V9SoD\MissedV9SoD;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class MissedBuilder implements SuffixBuilderInterface
{
    use ResolvesCombatLogFormat;

    public static function create(int $combatLogVersion): Suffix
    {
        /** @var class-string<Suffix> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className($combatLogVersion);
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC            => MissedV20::class,
                CombatLogVersion::CLASSIC_SOD_1_15_5 => MissedV9SoD::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => MissedV20::class,
                CombatLogVersion::RETAIL_11_0_5 => MissedV22::class,
            ],
        ];
    }
}
