<?php

namespace App\Logic\CombatLog\SpecialEvents;

use Illuminate\Support\Carbon;

interface SpecialEventBuilderInterface
{
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent;
}
