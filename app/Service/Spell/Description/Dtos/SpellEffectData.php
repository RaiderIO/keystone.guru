<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * A single row of the game client's SpellEffect DB2 table, reduced to what spell descriptions refer to.
 */
class SpellEffectData
{
    public function __construct(
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
     * Player abilities carry no base points and derive their value from the casting character's stats
     * instead. There is no character to scale against when rendering a description up front, and those
     * effects are exactly the ones that sit at zero - so a zero is read as "unknown" rather than shown,
     * which would claim an ability deals no damage at all.
     */
    public function hasKnownPoints(): bool
    {
        return $this->basePoints !== 0.0;
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
