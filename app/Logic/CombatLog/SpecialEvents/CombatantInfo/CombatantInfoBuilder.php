<?php

namespace App\Logic\CombatLog\SpecialEvents\CombatantInfo;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V21\CombatantInfoV21;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V9SoD\CombatantInfoV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class CombatantInfoBuilder implements SpecialEventBuilderInterface
{
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC_SOD_1_15_5, CombatLogVersion::CLASSIC_SOD_1_15_6, CombatLogVersion::CLASSIC_SOD_1_15_7 => new CombatantInfoV9SoD($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new CombatantInfoV21($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }

}
