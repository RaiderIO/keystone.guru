<?php

namespace App\Logic\CombatLog\CombatEvents\GenericData;

use App\Logic\CombatLog\CombatEvents\GenericData\Versions\All\GenericDataAll;

class GenericDataBuilder
{
    /**
     * @param int $combatLogVersion
     * @return GenericDataInterface
     */
    public static function create(int $combatLogVersion): GenericDataInterface
    {
        return new GenericDataAll();
    }
}
