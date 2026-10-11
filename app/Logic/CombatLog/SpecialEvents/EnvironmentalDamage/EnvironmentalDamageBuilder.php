<?php

namespace App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage;

use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V20\EnvironmentalDamageV20;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V22\EnvironmentalDamageV22;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V9\EnvironmentalDamageV9;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V9SoD\EnvironmentalDamageV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;
use Illuminate\Support\Carbon;

class EnvironmentalDamageBuilder implements SpecialEventBuilderInterface
{
    use ResolvesCombatLogFormat;

    /**
     * @param  array<int, mixed> $parameters
     * @return SpecialEvent
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent,
    ): SpecialEvent {
        /** @var class-string<SpecialEvent> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC            => EnvironmentalDamageV9::class,
                CombatLogVersion::CLASSIC_SOD_1_15_5 => EnvironmentalDamageV9SoD::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => EnvironmentalDamageV20::class,
                CombatLogVersion::RETAIL_11_0_5 => EnvironmentalDamageV22::class,
            ],
        ];
    }
}
