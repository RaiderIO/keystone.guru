<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Energize;

use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V22\EnergizeV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V9\EnergizeV9;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class EnergizeBuilder implements SuffixBuilderInterface
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
                CombatLogVersion::CLASSIC            => EnergizeV22::class,
                CombatLogVersion::CLASSIC_SOD_1_15_5 => EnergizeV9::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => EnergizeV22::class,
            ],
        ];
    }
}
