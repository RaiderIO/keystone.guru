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

    /**
     * Concrete prefix class per already-resolved event name. Unlike the suffix mapping this needs no
     * version in the key - {@see PREFIX_CLASS_MAPPING} has no per-version builders.
     *
     * @var array<string, class-string<Prefix>>
     */
    private static array $resolvedClassNames = [];

    /**
     * Caching stops above this many entries, for the same reason as
     * {@see Suffix::RESOLVED_CLASS_NAME_LIMIT}: resolution only requires the name to *start* with a
     * known prefix, so SPELL_<anything> is accepted, and this cache is written before the suffix
     * lookup gets its chance to reject the name. Past the cap resolution falls back to the scan.
     */
    private const int RESOLVED_CLASS_NAME_LIMIT = 1024;

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
        // Every line pays this lookup and the answer only ever depends on the event name, so resolve
        // it once and construct directly from the remembered class after that. See the note on
        // {@see Suffix::$resolvedClassNames} for why retaining this between requests is safe.
        if (isset(self::$resolvedClassNames[$eventName])) {
            $className = self::$resolvedClassNames[$eventName];

            return new $className($combatLogVersion);
        }

        foreach (self::PREFIX_CLASS_MAPPING as $prefix => $className) {
            if (Str::startsWith($eventName, $prefix)) {
                if (count(self::$resolvedClassNames) < self::RESOLVED_CLASS_NAME_LIMIT) {
                    self::$resolvedClassNames[$eventName] = $className;
                }

                return new $className($combatLogVersion);
            }
        }

        throw new Exception(sprintf('Unable to find prefix for %s!', $eventName));
    }
}
