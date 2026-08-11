<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * A single row of the game client's SpellEffect DB2 table, reduced to what spell descriptions refer to.
 */
class SpellEffectData
{
    /** SpellEffect.Effect values that produce an amount of damage or healing. */
    private const int EFFECT_SCHOOL_DAMAGE = 2;

    private const int EFFECT_HEAL = 10;

    private const int EFFECT_WEAPON_DAMAGE = 58;

    private const int EFFECT_WEAPON_DAMAGE_NOSCHOOL = 121;

    /** SpellEffect.EffectAura values that do the same over time. */
    private const int AURA_PERIODIC_DAMAGE = 3;

    private const int AURA_PERIODIC_HEAL = 8;

    private const int AURA_PERIODIC_LEECH = 53;

    private const int AURA_SCHOOL_ABSORB = 69;

    public function __construct(
        public readonly int    $effectType,
        public readonly int    $auraType,
        public readonly float  $basePoints,
        public readonly float  $variance,
        public readonly int    $periodMs,
        public readonly int    $chainTargets,
        public readonly ?float $radius,
        public readonly ?float $maxRadius,
    ) {
    }

    /**
     * Whether this effect's points can be shown at all.
     *
     * Creature abilities carry no base points and derive their value from the content they belong to.
     * Those effects are exactly the ones that sit at zero, so a zero is read as "unknown" rather than
     * shown, which would claim an ability deals no damage at all.
     */
    public function hasKnownPoints(): bool
    {
        return $this->basePoints !== 0.0;
    }

    /**
     * What this effect's points mean, which decides whether they scale with the content the caster
     * belongs to. Anything that is not an amount of damage or healing - a percentage, a stat modifier,
     * a number of targets - is the same at every key level.
     */
    public function getPointsKind(): SpellDescriptionValueKind
    {
        return match (true) {
            in_array($this->effectType, [
                self::EFFECT_SCHOOL_DAMAGE,
                self::EFFECT_WEAPON_DAMAGE,
                self::EFFECT_WEAPON_DAMAGE_NOSCHOOL,
            ], true)                                                                                 => SpellDescriptionValueKind::Damage,
            $this->effectType === self::EFFECT_HEAL                                                  => SpellDescriptionValueKind::Healing,
            in_array($this->auraType, [self::AURA_PERIODIC_DAMAGE, self::AURA_PERIODIC_LEECH], true) => SpellDescriptionValueKind::Damage,
            in_array($this->auraType, [self::AURA_PERIODIC_HEAL, self::AURA_SCHOOL_ABSORB], true)    => SpellDescriptionValueKind::Healing,
            default                                                                                  => SpellDescriptionValueKind::Value,
        };
    }

    /** The lowest value this effect rolls, i.e. `$m` in a description. */
    public function getMinPoints(): float
    {
        return $this->basePoints - ($this->basePoints * $this->variance / 2);
    }

    /** The highest value this effect rolls, i.e. `$M` in a description. */
    public function getMaxPoints(): float
    {
        return $this->basePoints + ($this->basePoints * $this->variance / 2);
    }
}
