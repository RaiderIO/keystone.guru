<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use Illuminate\Support\Str;

abstract class Prefix implements HasParameters
{
    use ValidatesParameterCount;

    public const PREFIX_SWING          = 'SWING';
    public const PREFIX_RANGE          = 'RANGE';
    public const PREFIX_SPELL_PERIODIC = 'SPELL_PERIODIC';
    public const PREFIX_SPELL_BUILDING = 'SPELL_BUILDING';
    public const PREFIX_SPELL          = 'SPELL';

    public const PREFIX_ALL = [
        self::PREFIX_SWING,
        self::PREFIX_RANGE,
        self::PREFIX_SPELL_PERIODIC,
        self::PREFIX_SPELL_BUILDING,
        self::PREFIX_SPELL,
    ];

    private const PREFIX_CLASS_MAPPING = [
        self::PREFIX_SWING          => Swing::class,
        self::PREFIX_RANGE          => Range::class,
        self::PREFIX_SPELL          => Spell::class,
        self::PREFIX_SPELL_PERIODIC => SpellPeriodic::class,
        self::PREFIX_SPELL_BUILDING => SpellBuilding::class,
    ];

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        $this->validateParameters($parameters);

        return $this;
    }


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
