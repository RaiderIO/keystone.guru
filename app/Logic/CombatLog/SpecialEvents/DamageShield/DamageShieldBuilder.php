<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageShield;

use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V20\DamageShieldV20;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V22\DamageShieldV22;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V9\DamageShieldV9;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V9SoD\DamageShieldV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;
use Illuminate\Support\Carbon;

class DamageShieldBuilder implements SpecialEventBuilderInterface
{
    use ResolvesCombatLogFormat;

    /**
     * @param  array<int, mixed>                  $parameters
     * @return SpecialEvent&DamageShieldInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent,
    ): SpecialEvent {
        /** @var class-string<SpecialEvent&DamageShieldInterface> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC            => DamageShieldV9::class,
                CombatLogVersion::CLASSIC_SOD_1_15_5 => DamageShieldV9SoD::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => DamageShieldV20::class,
                CombatLogVersion::RETAIL_11_0_5 => DamageShieldV22::class,
            ],
        ];
    }
}
