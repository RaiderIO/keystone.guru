<?php

namespace App\Logic\CombatLog\SpecialEvents\DamageSplit;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V20\DamageSplitV20;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\Versions\V22\DamageSplitV22;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class DamageSplitBuilder implements SpecialEventBuilderInterface
{
    /**
     * @return SpecialEvent|DamageSplitInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC, CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2 => new DamageSplitV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new DamageSplitV22($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
