<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced;

use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V20\AdvancedDataV20;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V22\AdvancedDataV22;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9\AdvancedDataV9;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V9SoD\AdvancedDataV9SoD;
use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;

class AdvancedDataBuilder
{
    use ResolvesCombatLogFormat;

    public static function create(int $combatLogVersion): AdvancedDataInterface
    {
        /** @var class-string<AdvancedDataInterface> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className();
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC            => AdvancedDataV9::class,
                CombatLogVersion::CLASSIC_SOD_1_15_5 => AdvancedDataV9SoD::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => AdvancedDataV20::class,
                CombatLogVersion::RETAIL_11_0_5 => AdvancedDataV22::class,
            ],
        ];
    }
}
