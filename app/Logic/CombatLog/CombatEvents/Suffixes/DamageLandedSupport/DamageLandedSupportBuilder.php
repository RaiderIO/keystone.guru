<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport;

use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\V20\DamageLandedSupportV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\V22\DamageLandedSupportV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class DamageLandedSupportBuilder implements SuffixBuilderInterface
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
                CombatLogVersion::CLASSIC => DamageLandedSupportV20::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => DamageLandedSupportV20::class,
                CombatLogVersion::RETAIL_11_0_5 => DamageLandedSupportV22::class,
            ],
        ];
    }
}
