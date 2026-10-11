<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;

use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22\AuraAppliedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22_1\AuraAppliedV22_1;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class AuraAppliedBuilder implements SuffixBuilderInterface
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
                CombatLogVersion::CLASSIC => AuraAppliedV22::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => AuraAppliedV22::class,
                CombatLogVersion::RETAIL_11_2_0 => AuraAppliedV22_1::class,
            ],
        ];
    }
}
