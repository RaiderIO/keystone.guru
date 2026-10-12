<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageSplit;

use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V20\DamageSplitV20;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V22\DamageSplitV22;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V9SoD\DamageSplitV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;
use Illuminate\Support\Carbon;

class DamageSplitBuilder implements SpecialEventBuilderInterface
{
    use ResolvesCombatLogFormat;

    /**
     * @param  array<int, mixed>                 $parameters
     * @return SpecialEvent&DamageSplitInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent,
    ): SpecialEvent {
        /** @var class-string<SpecialEvent&DamageSplitInterface> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC            => DamageSplitV20::class,
                CombatLogVersion::CLASSIC_SOD_1_15_5 => DamageSplitV9SoD::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => DamageSplitV20::class,
                CombatLogVersion::RETAIL_11_0_5 => DamageSplitV22::class,
            ],
        ];
    }
}
