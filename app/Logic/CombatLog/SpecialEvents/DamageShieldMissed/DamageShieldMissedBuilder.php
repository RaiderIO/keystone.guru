<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageShieldMissed;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\Versions\DamageShieldMissedV20;
use App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\Versions\DamageShieldMissedV9;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Carbon\Carbon;

class DamageShieldMissedBuilder implements SpecialEventBuilderInterface
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
                return new DamageShieldMissedV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
            default:
                return new DamageShieldMissedV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
        }
    }
}
