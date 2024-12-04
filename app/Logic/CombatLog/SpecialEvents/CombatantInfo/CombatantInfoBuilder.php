<?php

namespace App\Logic\CombatLog\SpecialEvents\CombatantInfo;

use App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V21\CombatantInfoV21;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use Illuminate\Support\Carbon;

class CombatantInfoBuilder  implements SpecialEventBuilderInterface
{
    public static function create(int $combatLogVersion, Carbon $timestamp, string $eventName, array $parameters, string $rawEvent): SpecialEvent
    {
        /** @noinspection PhpMatchExpressionWithOnlyDefaultArmInspection */
        return match ($combatLogVersion) {
            default => new CombatantInfoV21($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent),
        };
    }

}
