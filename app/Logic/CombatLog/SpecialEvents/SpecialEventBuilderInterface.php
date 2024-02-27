<?php

namespace App\Logic\CombatLog\SpecialEvents;

use Carbon\Carbon;

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
