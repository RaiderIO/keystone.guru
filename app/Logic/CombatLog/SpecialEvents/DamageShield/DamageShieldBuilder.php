<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageShield;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V20\DamageShieldV20;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V22\DamageShieldV22;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V9\DamageShieldV9;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\V9SoD\DamageShieldV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class DamageShieldBuilder implements SpecialEventBuilderInterface
{
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC => new DamageShieldV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            CombatLogVersion::CLASSIC_SOD_1_15_5, CombatLogVersion::CLASSIC_SOD_1_15_6, CombatLogVersion::CLASSIC_SOD_1_15_7 => new DamageShieldV9SoD($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2 => new DamageShieldV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new DamageShieldV22($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
