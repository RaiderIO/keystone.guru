<?php

namespace App\Logic\CombatLog\Events\Prefixes;

use App\Logic\CombatLog\Events\BaseEvent;
use Illuminate\Support\Str;

abstract class Prefix extends BaseEvent
{
    public const PREFIX_SWING          = 'SWING';
    public const PREFIX_RANGE          = 'RANGE';
    public const PREFIX_SPELL_PERIODIC = 'SPELL_PERIODIC';
    public const PREFIX_SPELL_BUILDING = 'SPELL_BUILDING';
    public const PREFIX_SPELL          = 'SPELL';

    private const PREFIX_CLASS_MAPPING = [
        self::PREFIX_SWING          => Swing::class,
        self::PREFIX_RANGE          => Range::class,
        self::PREFIX_SPELL          => Spell::class,
        self::PREFIX_SPELL_PERIODIC => SpellPeriodic::class,
        self::PREFIX_SPELL_BUILDING => SpellBuilding::class,
    ];


    /**
     * @param string $eventName
     * @return Prefix|null
     */
    public static function createFromEventName(string $eventName): ?Prefix
    {
        $result = null;

        foreach (self::PREFIX_CLASS_MAPPING as $prefix => $className) {
            if (Str::startsWith($eventName, $prefix)) {
                $result = new $className();
                break;
            }
        }

        return $result;
    }
}
