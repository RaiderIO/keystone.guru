<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded;

use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\V20\DamageLandedV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\V22\DamageLandedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class DamageLandedBuilder implements SuffixBuilderInterface
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
                CombatLogVersion::CLASSIC => DamageLandedV20::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => DamageLandedV20::class,
                CombatLogVersion::RETAIL_11_0_5 => DamageLandedV22::class,
            ],
        ];
    }
}
