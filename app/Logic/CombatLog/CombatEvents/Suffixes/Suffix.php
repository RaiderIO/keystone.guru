<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\DamageBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\DamageLandedBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLandedSupport\DamageLandedSupportBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\DamageSupportBuilder;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use Exception;
use Illuminate\Support\Str;

abstract class Suffix implements HasParameters
{
    use ValidatesParameterCount;

    public const SUFFIX_DAMAGE_LANDED_SUPPORT = 'DAMAGE_LANDED_SUPPORT';
    public const SUFFIX_DAMAGE_LANDED  = 'DAMAGE_LANDED';
    public const SUFFIX_DAMAGE_SUPPORT = 'DAMAGE_SUPPORT';
    public const SUFFIX_DAMAGE         = 'DAMAGE';
    public const SUFFIX_MISSED         = 'MISSED';
    public const SUFFIX_HEAL_ABSORBED  = 'HEAL_ABSORBED';
    public const SUFFIX_HEAL_SUPPORT   = 'HEAL_SUPPORT';
    public const SUFFIX_HEAL           = 'HEAL';
    public const SUFFIX_ABSORBED       = 'ABSORBED';
    public const SUFFIX_ENERGIZE       = 'ENERGIZE';
    public const SUFFIX_DRAIN          = 'DRAIN';
    public const SUFFIX_LEECH          = 'LEECH';
    public const SUFFIX_EMPOWER_INTERRUPT = 'EMPOWER_INTERRUPT';
    public const SUFFIX_INTERRUPT      = 'INTERRUPT';
    public const SUFFIX_DISPEL_FAILED  = 'DISPEL_FAILED';
    public const SUFFIX_DISPEL         = 'DISPEL';
    public const SUFFIX_STOLEN         = 'STOLEN';
    public const SUFFIX_EXTRA_ATTACKS  = 'EXTRA_ATTACKS';
    public const SUFFIX_AURA_APPLIED   = 'AURA_APPLIED';
    public const SUFFIX_AURA_REMOVED   = 'AURA_REMOVED';
    public const SUFFIX_AURA_APPLIED_DOSE = 'AURA_APPLIED_DOSE';
    public const SUFFIX_AURA_REMOVED_DOSE = 'AURA_REMOVED_DOSE';
    public const SUFFIX_AURA_REFRESH   = 'AURA_REFRESH';
    public const SUFFIX_AURA_BROKEN_SPELL = 'AURA_BROKEN_SPELL';
    public const SUFFIX_AURA_BROKEN    = 'AURA_BROKEN';
    public const SUFFIX_CAST_START     = 'CAST_START';
    public const SUFFIX_CAST_SUCCESS   = 'CAST_SUCCESS';
    public const SUFFIX_CAST_FAILED    = 'CAST_FAILED';
    public const SUFFIX_INSTAKILL      = 'INSTAKILL';
    public const SUFFIX_DURABILITY_DAMAGE = 'DURABILITY_DAMAGE';
    public const SUFFIX_DURABILITY_DAMAGE_ALL = 'DURABILITY_DAMAGE_ALL';
    public const SUFFIX_CREATE         = 'CREATE';
    public const SUFFIX_SUMMON         = 'SUMMON';
    public const SUFFIX_EMPOWER_START  = 'EMPOWER_START';
    public const SUFFIX_EMPOWER_END    = 'EMPOWER_END';

    public const SUFFIX_ALL = [
        self::SUFFIX_DAMAGE_LANDED_SUPPORT,
        self::SUFFIX_DAMAGE_LANDED,
        self::SUFFIX_DAMAGE_SUPPORT,
        self::SUFFIX_DAMAGE,
        self::SUFFIX_MISSED,
        self::SUFFIX_HEAL_ABSORBED,
        self::SUFFIX_HEAL_SUPPORT,
        self::SUFFIX_HEAL,
        self::SUFFIX_ABSORBED,
        self::SUFFIX_ENERGIZE,
        self::SUFFIX_DRAIN,
        self::SUFFIX_LEECH,
        self::SUFFIX_EMPOWER_INTERRUPT,
        self::SUFFIX_INTERRUPT,
        self::SUFFIX_DISPEL_FAILED,
        self::SUFFIX_DISPEL,
        self::SUFFIX_STOLEN,
        self::SUFFIX_EXTRA_ATTACKS,
        self::SUFFIX_AURA_APPLIED,
        self::SUFFIX_AURA_REMOVED,
        self::SUFFIX_AURA_APPLIED_DOSE,
        self::SUFFIX_AURA_REMOVED_DOSE,
        self::SUFFIX_AURA_REFRESH,
        self::SUFFIX_AURA_BROKEN_SPELL,
        self::SUFFIX_AURA_BROKEN,
        self::SUFFIX_CAST_START,
        self::SUFFIX_CAST_SUCCESS,
        self::SUFFIX_CAST_FAILED,
        self::SUFFIX_INSTAKILL,
        self::SUFFIX_DURABILITY_DAMAGE_ALL,
        self::SUFFIX_DURABILITY_DAMAGE,
        self::SUFFIX_CREATE,
        self::SUFFIX_SUMMON,
        self::SUFFIX_EMPOWER_START,
        self::SUFFIX_EMPOWER_END,
    ];

    private const SUFFIX_CLASS_MAPPING = [
        self::SUFFIX_DAMAGE_LANDED_SUPPORT => DamageLandedSupportBuilder::class,
        self::SUFFIX_DAMAGE_LANDED         => DamageLandedBuilder::class,
        self::SUFFIX_DAMAGE_SUPPORT        => DamageSupportBuilder::class,
        self::SUFFIX_DURABILITY_DAMAGE_ALL => DurabilityDamageAll::class,
        self::SUFFIX_DURABILITY_DAMAGE     => DurabilityDamage::class,
        self::SUFFIX_DAMAGE                => DamageBuilder::class,
        self::SUFFIX_MISSED                => Missed::class,
        self::SUFFIX_HEAL_ABSORBED         => HealAbsorbed::class,
        self::SUFFIX_HEAL_SUPPORT          => HealSupport::class,
        self::SUFFIX_HEAL                  => Heal::class,
        self::SUFFIX_ABSORBED              => Absorbed::class,
        self::SUFFIX_ENERGIZE              => Energize::class,
        self::SUFFIX_DRAIN                 => Drain::class,
        self::SUFFIX_LEECH                 => Leech::class,
        self::SUFFIX_EMPOWER_INTERRUPT     => EmpowerInterrupt::class,
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
        self::SUFFIX_CREATE                => Create::class,
        self::SUFFIX_SUMMON                => Summon::class,
        self::SUFFIX_EMPOWER_START         => EmpowerStart::class,
        self::SUFFIX_EMPOWER_END           => EmpowerEnd::class,
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
    public static function createFromEventName(int $combatLogVersion, string $eventName): Suffix
    {
        foreach (self::SUFFIX_CLASS_MAPPING as $prefix => $className) {
            if (Str::endsWith($eventName, $prefix)) {
                $suffix = new $className($combatLogVersion);
                if ($suffix instanceof SuffixBuilderInterface) {
                    return $suffix::create($combatLogVersion);
                }

                return $suffix;
            }
        }

        throw new Exception(sprintf('Unable to find suffix for %s!', $eventName));
    }
}
