<?php

namespace App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V20\EnvironmentalDamageV20;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V22\EnvironmentalDamageV22;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V9\EnvironmentalDamageV9;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\V9SoD\EnvironmentalDamageV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class EnvironmentalDamageBuilder implements SpecialEventBuilderInterface
{
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC => new EnvironmentalDamageV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            CombatLogVersion::CLASSIC_SOD_1_15_5, CombatLogVersion::CLASSIC_SOD_1_15_6, CombatLogVersion::CLASSIC_SOD_1_15_7 => new EnvironmentalDamageV9SoD($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2 => new EnvironmentalDamageV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new EnvironmentalDamageV22($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
