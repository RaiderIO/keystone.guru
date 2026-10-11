<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Damage;

use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V22\DamageV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class DamageBuilder implements SuffixBuilderInterface
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
                CombatLogVersion::CLASSIC            => DamageV20::class,
                CombatLogVersion::CLASSIC_SOD_1_15_5 => DamageV22::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => DamageV20::class,
                CombatLogVersion::RETAIL_11_0_5 => DamageV22::class,
            ],
        ];
    }
}
