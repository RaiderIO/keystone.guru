<?php

namespace App\Logic\CombatLog\SpecialEvents\SpellAbsorbed;

use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\Versions\V20\SpellAbsorbedV20;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\Versions\V9\SpellAbsorbedV9;
use Carbon\Carbon;

class SpellAbsorbedBuilder implements SpecialEventBuilderInterface
{
    /**
     * @return SpecialEvent|SpellAbsorbedInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        return match ($combatLogVersion) {
            CombatLogVersion::CLASSIC => new SpellAbsorbedV9($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
            default => new SpellAbsorbedV20($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }
}
