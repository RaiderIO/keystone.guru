<?php

namespace App\Logic\CombatLog\SpecialEvents;

use Carbon\Carbon;

interface SpecialEventBuilderInterface
{
    /**
     * @param int    $combatLogVersion
     * @param Carbon $timestamp
     * @param string $eventName
     * @param array  $parameters
     * @param string $rawEvent
     * @return SpecialEvent
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent;
}
