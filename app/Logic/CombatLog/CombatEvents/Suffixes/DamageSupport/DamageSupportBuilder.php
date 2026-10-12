<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport;

use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V20\DamageSupportV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V22\DamageSupportV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class DamageSupportBuilder implements SuffixBuilderInterface
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
                CombatLogVersion::CLASSIC => DamageSupportV20::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => DamageSupportV20::class,
                CombatLogVersion::RETAIL_11_0_5 => DamageSupportV22::class,
            ],
        ];
    }
}
