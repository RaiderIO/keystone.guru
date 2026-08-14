<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * What a number in a spell description means, which decides whether it scales.
 *
 * Only damage and healing are coefficients of the content the caster belongs to; a duration, a radius
 * or a percentage is the same number at every key level.
 */
enum SpellDescriptionValueKind: string
{
    case Damage = 'damage';

    case Healing = 'healing';

    case Duration = 'duration';

    case Period = 'period';

    case Radius = 'radius';

    case Count = 'count';

    /** A number we resolved but cannot classify - a percentage, a modifier, a named variable. */
    case Value = 'value';

    public function isScalable(): bool
    {
        return $this === self::Damage || $this === self::Healing;
    }
}
