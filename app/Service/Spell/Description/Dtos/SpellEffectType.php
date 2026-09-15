<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * The values of the game client's `SpellEffect.Effect` column that produce an amount of damage or healing.
 *
 * The column has hundreds of values and the vast majority say nothing about an amount, so only the ones
 * that do are named here - an effect type without a case is simply not an amount. That is also why the
 * effect keeps the raw int rather than this enum: a typed property would reject every unnamed value.
 */
enum SpellEffectType: int
{
    case SchoolDamage = 2;

    case Heal = 10;

    case WeaponDamage = 58;

    case WeaponDamageNoSchool = 121;

    /** What the points of an effect of this type mean. */
    public function getPointsKind(): SpellDescriptionValueKind
    {
        return match ($this) {
            self::Heal => SpellDescriptionValueKind::Healing,
            default    => SpellDescriptionValueKind::Damage,
        };
    }
}
