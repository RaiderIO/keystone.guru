<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use Exception;
use Illuminate\Support\Str;

abstract class Prefix implements HasParameters
{
    use ValidatesParameterCount;

    // Bit of a special one, SWING_DAMAGE_LANDED_SUPPORT is actually a spell and not a swing.
    // We have to include the full name here so it doesn't collide with anything else
    public const PREFIX_SWING_DAMAGE_LANDED_SUPPORT = 'SWING_DAMAGE_LANDED_SUPPORT';

    public const PREFIX_SWING = 'SWING';

    public const PREFIX_RANGE = 'RANGE';

    public const PREFIX_SPELL_PERIODIC = 'SPELL_PERIODIC';

    public const PREFIX_SPELL_BUILDING = 'SPELL_BUILDING';

    public const PREFIX_SPELL = 'SPELL';

    public const PREFIX_ALL = [
        self::PREFIX_SWING_DAMAGE_LANDED_SUPPORT,
        self::PREFIX_SWING,
        self::PREFIX_RANGE,
        self::PREFIX_SPELL_PERIODIC,
        self::PREFIX_SPELL_BUILDING,
        self::PREFIX_SPELL,
    ];

    private const PREFIX_CLASS_MAPPING = [
        self::PREFIX_SWING_DAMAGE_LANDED_SUPPORT => SwingDamageLandedSupport::class,
        self::PREFIX_SWING                       => Swing::class,
        self::PREFIX_RANGE                       => Range::class,
        self::PREFIX_SPELL                       => Spell::class,
        self::PREFIX_SPELL_PERIODIC              => SpellPeriodic::class,
        self::PREFIX_SPELL_BUILDING              => SpellBuilding::class,
    ];

    public function __construct(protected int $combatLogVersion)
    {
    }

    /**
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        $this->validateParameters($parameters);

        return $this;
    }

    /**
     * @throws Exception
     */
    public static function createFromEventName(int $combatLogVersion, string $eventName): Prefix
    {
        foreach (self::PREFIX_CLASS_MAPPING as $prefix => $className) {
            if (Str::startsWith($eventName, $prefix)) {
                return new $className($combatLogVersion);
            }
        }

        throw new Exception(sprintf('Unable to find prefix for %s!', $eventName));
    }
}
