<?php

namespace App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\EnvironmentalDamageV20;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\EnvironmentalDamageV9;
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
            default => new EnvironmentalDamageV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
