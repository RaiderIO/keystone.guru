<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageShieldMissed;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\Versions\V20\DamageShieldMissedV20;
use App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\Versions\V9\DamageShieldMissedV9;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class DamageShieldMissedBuilder implements SpecialEventBuilderInterface
{
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC => new DamageShieldMissedV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new DamageShieldMissedV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
