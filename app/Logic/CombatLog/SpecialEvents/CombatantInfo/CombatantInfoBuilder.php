<?php

namespace App\Logic\CombatLog\SpecialEvents\CombatantInfo;

use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V21\CombatantInfoV21;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V22\CombatantInfoV22;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V9SoD\CombatantInfoV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;
use Illuminate\Support\Carbon;

class CombatantInfoBuilder implements SpecialEventBuilderInterface
{
    use ResolvesCombatLogFormat;

    /**
     * @param  array<int, mixed>                   $parameters
     * @return SpecialEvent&CombatantInfoInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent,
    ): SpecialEvent {
        /** @var class-string<SpecialEvent&CombatantInfoInterface> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC            => CombatantInfoV22::class,
                CombatLogVersion::CLASSIC_SOD_1_15_5 => CombatantInfoV9SoD::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => CombatantInfoV21::class,
                CombatLogVersion::RETAIL_12_0_1 => CombatantInfoV22::class,
            ],
        ];
    }
}
