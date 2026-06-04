<?php

namespace App\Logic\CombatLog\SpecialEvents\CombatantInfo;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V21\CombatantInfoV21;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V22\CombatantInfoV22;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V9SoD\CombatantInfoV9SoD;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class CombatantInfoBuilder implements SpecialEventBuilderInterface
{
    /**
     * @return SpecialEvent&CombatantInfoInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent,
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC_SOD_1_15_5, CombatLogVersion::CLASSIC_SOD_1_15_6, CombatLogVersion::CLASSIC_SOD_1_15_7, CombatLogVersion::CLASSIC_TBC_2_5_5 => new CombatantInfoV9SoD($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            CombatLogVersion::RETAIL_10_1_0, CombatLogVersion::RETAIL_11_0_2, CombatLogVersion::RETAIL_11_0_5,
            CombatLogVersion::RETAIL_11_0_7, CombatLogVersion::RETAIL_11_1_0, CombatLogVersion::RETAIL_11_1_7,
            CombatLogVersion::RETAIL_11_2_0 => new CombatantInfoV21($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default                         => new CombatantInfoV22($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
