<?php

namespace App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport\Versions\V20\SpellAbsorbedSupportV20;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport\Versions\V9\SpellAbsorbedSupportV9;
use Illuminate\Support\Carbon;

class SpellAbsorbedSupportBuilder implements SpecialEventBuilderInterface
{
    /**
     * @return SpecialEvent|SpellAbsorbedSupportInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC, CombatLogVersion::CLASSIC_SOD_1_15_5, CombatLogVersion::CLASSIC_SOD_1_15_6 => new SpellAbsorbedSupportV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new SpellAbsorbedSupportV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
