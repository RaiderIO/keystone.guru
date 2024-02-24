<?php

namespace App\Logic\CombatLog\CombatEvents\GenericData;

use App\Logic\CombatLog\CombatEvents\GenericData\Versions\All\GenericDataAll;

class GenericDataBuilder
{
    /**
     * @return GenericDataInterface
     */
    public static function create(int $combatLogVersion): GenericDataInterface
    {
        return new GenericDataAll();
    }
}
