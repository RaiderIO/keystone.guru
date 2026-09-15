<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * The values of the game client's `SpellEffect.EffectAura` column that produce an amount of damage or
 * healing over time.
 *
 * Named on the same terms as {@see SpellEffectType}: only the values that carry an amount have a case,
 * and the effect itself keeps the raw int so an unnamed aura is still stored and exported.
 */
enum SpellEffectAuraType: int
{
    case PeriodicDamage = 3;

    case PeriodicHeal = 8;

    case PeriodicLeech = 53;

    case SchoolAbsorb = 69;

    /** What the points of an aura of this type mean. */
    public function getPointsKind(): SpellDescriptionValueKind
    {
        return match ($this) {
            self::PeriodicHeal, self::SchoolAbsorb => SpellDescriptionValueKind::Healing,
            default                                => SpellDescriptionValueKind::Damage,
        };
    }
}
