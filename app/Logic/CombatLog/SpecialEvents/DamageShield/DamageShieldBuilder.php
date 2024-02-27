<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageShield;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\DamageShieldV20;
use App\Logic\CombatLog\SpecialEvents\DamageShield\Versions\DamageShieldV9;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Carbon\Carbon;

class DamageShieldBuilder implements SpecialEventBuilderInterface
{
    public static function create(
        int $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC => new DamageShieldV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new DamageShieldV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
