<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\GenericData;
use Illuminate\Support\Str;

abstract class Suffix extends GenericData
{
    public const SUFFIX_DAMAGE                = 'DAMAGE';
    public const SUFFIX_MISSED                = 'MISSED';
    public const SUFFIX_HEAL_ABSORBED         = 'HEAL_ABSORBED';
    public const SUFFIX_HEAL                  = 'HEAL';
    public const SUFFIX_ABSORBED              = 'ABSORBED';
    public const SUFFIX_ENERGIZE              = 'ENERGIZE';
    public const SUFFIX_DRAIN                 = 'DRAIN';
    public const SUFFIX_LEECH                 = 'LEECH';
    public const SUFFIX_INTERRUPT             = 'INTERRUPT';
    public const SUFFIX_DISPEL_FAILED         = 'DISPEL_FAILED';
    public const SUFFIX_DISPEL                = 'DISPEL';
    public const SUFFIX_STOLEN                = 'STOLEN';
    public const SUFFIX_EXTRA_ATTACKS         = 'EXTRA_ATTACKS';
    public const SUFFIX_AURA_APPLIED          = 'AURA_APPLIED';
    public const SUFFIX_AURA_REMOVED          = 'AURA_REMOVED';
    public const SUFFIX_AURA_APPLIED_DOSE     = 'AURA_APPLIED_DOSE';
    public const SUFFIX_AURA_REMOVED_DOSE     = 'AURA_REMOVED_DOSE';
    public const SUFFIX_AURA_REFRESH          = 'AURA_REFRESH';
    public const SUFFIX_AURA_BROKEN_SPELL     = 'AURA_BROKEN_SPELL';
    public const SUFFIX_AURA_BROKEN           = 'AURA_BROKEN';
    public const SUFFIX_CAST_START            = 'CAST_START';
    public const SUFFIX_CAST_SUCCESS          = 'CAST_SUCCESS';
    public const SUFFIX_CAST_FAILED           = 'CAST_FAILED';
    public const SUFFIX_INSTAKILL             = 'INSTAKILL';
    public const SUFFIX_DURABILITY_DAMAGE     = 'DURABILITY_DAMAGE';
    public const SUFFIX_DURABILITY_DAMAGE_ALL = 'DURABILITY_DAMAGE_ALL';
    public const SUFFIX_CREATE                = 'CREATE';
    public const SUFFIX_SUMMON                = 'SUMMON';
    public const SUFFIX_RESURRECT             = 'RESURRECT';
    public const PREFIX_ENVIRONMENTAL_DAMAGE  = 'ENVIRONMENTAL_DAMAGE';

    public const SUFFIX_CLASS_MAPPING = [
        self::SUFFIX_DAMAGE                => Damage::class,
        self::SUFFIX_MISSED                => Missed::class,
        self::SUFFIX_HEAL_ABSORBED         => HealAbsorbed::class,
        self::SUFFIX_HEAL                  => Heal::class,
        self::SUFFIX_ABSORBED              => Absorbed::class,
        self::SUFFIX_ENERGIZE              => Energize::class,
        self::SUFFIX_DRAIN                 => Drain::class,
        self::SUFFIX_LEECH                 => Leech::class,
        self::SUFFIX_INTERRUPT             => Interrupt::class,
        self::SUFFIX_DISPEL_FAILED         => DispelFailed::class,
        self::SUFFIX_DISPEL                => Dispel::class,
        self::SUFFIX_STOLEN                => Stolen::class,
        self::SUFFIX_EXTRA_ATTACKS         => ExtraAttacks::class,
        self::SUFFIX_AURA_APPLIED          => AuraApplied::class,
        self::SUFFIX_AURA_REMOVED          => AuraRemoved::class,
        self::SUFFIX_AURA_APPLIED_DOSE     => AuraAppliedDose::class,
        self::SUFFIX_AURA_REMOVED_DOSE     => AuraRemovedDose::class,
        self::SUFFIX_AURA_REFRESH          => AuraRefresh::class,
        self::SUFFIX_AURA_BROKEN_SPELL     => AuraBrokenSpell::class,
        self::SUFFIX_AURA_BROKEN           => AuraBroken::class,
        self::SUFFIX_CAST_START            => CastStart::class,
        self::SUFFIX_CAST_SUCCESS          => CastSuccess::class,
        self::SUFFIX_CAST_FAILED           => CastFailed::class,
        self::SUFFIX_INSTAKILL             => Instakill::class,
        self::SUFFIX_DURABILITY_DAMAGE_ALL => DurabilityDamageAll::class,
        self::SUFFIX_DURABILITY_DAMAGE     => DurabilityDamage::class,
        self::SUFFIX_CREATE                => Create::class,
        self::SUFFIX_SUMMON                => Summon::class,
        self::SUFFIX_RESURRECT             => Resurrect::class,
        self::PREFIX_ENVIRONMENTAL_DAMAGE  => EnvironmentalDamage::class,
    ];


    /**
     * @param string $eventName
     * @return Suffix|null
     */
    public static function createFromEventName(string $eventName): ?Suffix
    {
        $result = null;

        foreach (self::SUFFIX_CLASS_MAPPING as $prefix => $className) {
            if (Str::endsWith($eventName, $prefix)) {
                $result = new $className();
                break;
            }
        }

        return $result;
    }

}
