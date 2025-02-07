<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterEnd;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V20\EncounterEndV20;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V9\EncounterEndV9;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class EncounterEndBuilder implements SpecialEventBuilderInterface
{
    /**
     * @return SpecialEvent|EncounterEndInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC, CombatLogVersion::CLASSIC_SOD_1_15_5, CombatLogVersion::CLASSIC_SOD_1_15_6 => new EncounterEndV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new EncounterEndV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
