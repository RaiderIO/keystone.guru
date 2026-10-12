<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved;

use App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\V22\AuraRemovedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved\V22_1\AuraRemovedV22_1;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\SuffixBuilderInterface;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class AuraRemovedBuilder implements SuffixBuilderInterface
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
                CombatLogVersion::CLASSIC => AuraRemovedV22::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => AuraRemovedV22::class,
                CombatLogVersion::RETAIL_11_2_0 => AuraRemovedV22_1::class,
            ],
        ];
    }
}
