<?php

namespace App\Logic\CombatLog\SpecialEvents;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use App\Logic\CombatLog\SpecialEvents\DamageShield\DamageShieldBuilder;
use App\Logic\CombatLog\SpecialEvents\DamageShieldMissed\DamageShieldMissedBuilder;
use App\Logic\CombatLog\SpecialEvents\EnvironmentalDamage\EnvironmentalDamageBuilder;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\SpellAbsorbedBuilder;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

abstract class SpecialEvent extends BaseEvent implements HasParameters
{
    use ValidatesParameterCount;

    public const SPECIAL_EVENT_COMBAT_LOG_VERSION = 'COMBAT_LOG_VERSION';

    public const SPECIAL_EVENT_ZONE_CHANGE = 'ZONE_CHANGE';

    public const SPECIAL_EVENT_MAP_CHANGE = 'MAP_CHANGE';

    public const SPECIAL_EVENT_CHALLENGE_MODE_START = 'CHALLENGE_MODE_START';

    public const SPECIAL_EVENT_CHALLENGE_MODE_END = 'CHALLENGE_MODE_END';

    public const SPECIAL_EVENT_ENCOUNTER_START = 'ENCOUNTER_START';

    public const SPECIAL_EVENT_ENCOUNTER_END = 'ENCOUNTER_END';

    public const SPECIAL_EVENT_COMBATANT_INFO = 'COMBATANT_INFO';

    public const SPECIAL_EVENT_PARTY_KILL = 'PARTY_KILL';

    public const SPECIAL_EVENT_UNIT_DESTROYED = 'UNIT_DESTROYED';

    public const SPECIAL_EVENT_UNIT_DIED = 'UNIT_DIED';

    public const SPECIAL_EVENT_UNIT_DISSIPATES = 'UNIT_DISSIPATES';

    // Putting it here since it's a weird event that I don't want to decypher at the moment
    public const SPECIAL_EVENT_SPELL_ABSORBED = 'SPELL_ABSORBED';

    public const SPECIAL_EVENT_ENVIRONMENTAL_DAMAGE = 'ENVIRONMENTAL_DAMAGE';

    public const SPECIAL_EVENT_DAMAGE_SPLIT = 'DAMAGE_SPLIT';

    public const SPECIAL_EVENT_DAMAGE_SHIELD_MISSED = 'DAMAGE_SHIELD_MISSED';

    public const SPECIAL_EVENT_DAMAGE_SHIELD = 'DAMAGE_SHIELD';

    public const SPECIAL_EVENT_SPELL_RESURRECT = 'SPELL_RESURRECT';

    public const SPECIAL_EVENT_EMOTE = 'EMOTE';

    public const SPECIAL_EVENT_ENCHANT_APPLIED = 'ENCHANT_APPLIED';

    public const SPECIAL_EVENT_ENCHANT_REMOVED = 'ENCHANT_REMOVED';

    public const SPECIAL_EVENT_WORLD_MARKER_PLACED = 'WORLD_MARKER_PLACED';

    public const SPECIAL_EVENT_WORLD_MARKER_REMOVED = 'WORLD_MARKER_REMOVED';

    public const SPECIAL_EVENT_ALL = [
        self::SPECIAL_EVENT_COMBAT_LOG_VERSION,
        self::SPECIAL_EVENT_ZONE_CHANGE,
        self::SPECIAL_EVENT_MAP_CHANGE,

        self::SPECIAL_EVENT_CHALLENGE_MODE_START,
        self::SPECIAL_EVENT_CHALLENGE_MODE_END,

        self::SPECIAL_EVENT_ENCOUNTER_START,
        self::SPECIAL_EVENT_ENCOUNTER_END,

        self::SPECIAL_EVENT_COMBATANT_INFO,
        self::SPECIAL_EVENT_PARTY_KILL,

        self::SPECIAL_EVENT_UNIT_DESTROYED,
        self::SPECIAL_EVENT_UNIT_DIED,
        self::SPECIAL_EVENT_UNIT_DISSIPATES,

        self::SPECIAL_EVENT_SPELL_ABSORBED,
        self::SPECIAL_EVENT_ENVIRONMENTAL_DAMAGE,
        self::SPECIAL_EVENT_DAMAGE_SPLIT,
        self::SPECIAL_EVENT_DAMAGE_SHIELD_MISSED,
        self::SPECIAL_EVENT_DAMAGE_SHIELD,
        self::SPECIAL_EVENT_SPELL_RESURRECT,

        self::SPECIAL_EVENT_EMOTE,
        self::SPECIAL_EVENT_ENCHANT_APPLIED,
        self::SPECIAL_EVENT_ENCHANT_REMOVED,

        self::SPECIAL_EVENT_WORLD_MARKER_PLACED,
        self::SPECIAL_EVENT_WORLD_MARKER_REMOVED,
    ];

    private const SPECIAL_EVENT_CLASS_MAPPING = [
        self::SPECIAL_EVENT_COMBAT_LOG_VERSION => CombatLogVersion::class,
        self::SPECIAL_EVENT_ZONE_CHANGE        => ZoneChange::class,
        self::SPECIAL_EVENT_MAP_CHANGE         => MapChange::class,

        self::SPECIAL_EVENT_CHALLENGE_MODE_START => ChallengeModeStart::class,
        self::SPECIAL_EVENT_CHALLENGE_MODE_END   => ChallengeModeEnd::class,

        self::SPECIAL_EVENT_ENCOUNTER_START => EncounterStart::class,
        self::SPECIAL_EVENT_ENCOUNTER_END   => EncounterEnd::class,

        self::SPECIAL_EVENT_COMBATANT_INFO => CombatantInfo::class,
        self::SPECIAL_EVENT_PARTY_KILL     => PartyKill::class,

        self::SPECIAL_EVENT_UNIT_DESTROYED  => UnitDestroyed::class,
        self::SPECIAL_EVENT_UNIT_DIED       => UnitDied::class,
        self::SPECIAL_EVENT_UNIT_DISSIPATES => UnitDissipates::class,

        self::SPECIAL_EVENT_DAMAGE_SPLIT         => DamageSplit::class,
        self::SPECIAL_EVENT_SPELL_RESURRECT      => SpellResurrect::class,

        self::SPECIAL_EVENT_EMOTE           => Emote::class,
        self::SPECIAL_EVENT_ENCHANT_APPLIED => EnchantApplied::class,
        self::SPECIAL_EVENT_ENCHANT_REMOVED => EnchantRemoved::class,

        self::SPECIAL_EVENT_WORLD_MARKER_PLACED  => WorldMarkerPlaced::class,
        self::SPECIAL_EVENT_WORLD_MARKER_REMOVED => WorldMarkerRemoved::class,
    ];

    private const SPECIAL_EVENT_BUILDER_CLASS_MAPPING = [
        self::SPECIAL_EVENT_DAMAGE_SHIELD        => DamageShieldBuilder::class,
        self::SPECIAL_EVENT_SPELL_ABSORBED       => SpellAbsorbedBuilder::class,
        self::SPECIAL_EVENT_ENVIRONMENTAL_DAMAGE => EnvironmentalDamageBuilder::class,
        self::SPECIAL_EVENT_DAMAGE_SHIELD_MISSED => DamageShieldMissedBuilder::class,
    ];

    public function __construct(int $combatLogVersion, Carbon $timestamp, string $eventName, array $parameters, string $rawEvent)
    {
        parent::__construct($combatLogVersion, $timestamp, $eventName, $rawEvent);

        $this->setParameters($parameters);
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
    public static function createFromEventName(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent
    ): SpecialEvent {
        foreach (self::SPECIAL_EVENT_CLASS_MAPPING as $specialEvent => $className) {
            if (Str::startsWith($eventName, $specialEvent)) {
                return new $className($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
            }
        }

        foreach (self::SPECIAL_EVENT_BUILDER_CLASS_MAPPING as $specialEvent => $className) {
            if (Str::startsWith($eventName, $specialEvent)) {
                /** @var $className SpecialEventBuilderInterface */
                return $className::create($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
            }
        }

        throw new Exception(sprintf('Unable to find special event for %s!', $eventName));
    }
}
