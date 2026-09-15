<?php

namespace App\Logic\CombatLog\CombatEvents\Interfaces;

use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataInterface;

/**
 * Implemented by every event that carries the 8 generic combat log parameters (source/dest GUID, name and flags).
 *
 * Both {@see \App\Logic\CombatLog\CombatEvents\CombatLogEvent} and
 * {@see \App\Logic\CombatLog\SpecialEvents\GenericSpecialEvent} do - they just do not share a common ancestor that
 * declares it, so consumers that only need the generic data should type check against this interface rather than
 * against either concrete base class.
 */
interface HasGenericData
{
    public function getGenericData(): GenericDataInterface;
}
