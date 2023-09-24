<?php

namespace App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\EnvironmentalDamageV20;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\Versions\EnvironmentalDamageV9;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Carbon\Carbon;

class EnvironmentalDamageBuilder implements SpecialEventBuilderInterface
{
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        switch ($combatLogVersion) {
            case CombatLogVersion::CLASSIC:
                return new EnvironmentalDamageV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
            default:
                return new EnvironmentalDamageV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
        }
    }
}
