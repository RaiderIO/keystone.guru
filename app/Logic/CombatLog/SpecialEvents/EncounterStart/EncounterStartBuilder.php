<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterStart;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\Versions\V20\EncounterStartV20;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\Versions\V9\EncounterStartV9;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class EncounterStartBuilder implements SpecialEventBuilderInterface
{
    /**
     * @return SpecialEvent|EncounterStartInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC, CombatLogVersion::CLASSIC_SOD_1_15_5, CombatLogVersion::CLASSIC_SOD_1_15_6 => new EncounterStartV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new EncounterStartV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
